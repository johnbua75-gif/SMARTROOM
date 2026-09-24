<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReservationRequest;
use App\Http\Requests\Api\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use App\Models\AccessCard;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request, RoomAvailabilityService $availabilityService): JsonResponse
    {
        $this->authorize('create', Reservation::class);

        $payload = $request->validated();
        // App timezone is Asia/Manila — parse directly, no conversion needed
        $startAt = Carbon::parse($payload['start_at']);
        $endAt = Carbon::parse($payload['end_at']);

        $reservation = DB::transaction(function () use ($request, $payload, $startAt, $endAt, $availabilityService): Reservation {
            $scheduleConflict = $availabilityService->checkOfficialScheduleConflict(
                (int) $payload['classroom_id'],
                $startAt,
                $endAt,
                null,
                true
            );

            if ($scheduleConflict['has_conflict']) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Room is occupied by official schedule at selected time.',
                    'errors' => [
                        'classroom_id' => ['Room is occupied by official schedule at selected time.'],
                    ],
                    'conflicts' => $scheduleConflict['conflicts'],
                ], 422));
            }

            $reservationConflict = $availabilityService->checkReservationConflict(
                (int) $payload['classroom_id'],
                $startAt,
                $endAt,
                null,
                true
            );

            if ($reservationConflict['has_conflict']) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Room is already reserved at selected time.',
                    'errors' => [
                        'classroom_id' => ['Room is already reserved at selected time.'],
                    ],
                    'conflicts' => $reservationConflict['conflicts'],
                ], 422));
            }

            // Auto-approve reservations created by faculty users so they can immediately access
            $status = 'reserved';
            try {
                $user = $request->user();
                if ($user && isset($user->role) && $user->role === 'faculty') {
                    $status = 'approved';
                }
            } catch (\Throwable $e) {
                // ignore - default to reserved
            }

            $reservation = Reservation::create([
                'classroom_id' => $payload['classroom_id'],
                'user_id' => $request->user()->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => $status,
                'notes' => $payload['notes'] ?? null,
            ]);

            $reservation->load(['classroom', 'user']);

            return $reservation;
        });

        return response()->json([
            'message' => 'Reservation created successfully.',
            'data' => $reservation->fresh(['classroom', 'user']),
        ], 201);
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation, RoomAvailabilityService $availabilityService): JsonResponse
    {
        $this->authorize('update', $reservation);

        $payload = $request->validated();

        // App timezone is Asia/Manila — parse directly, no conversion needed
        $startAt = isset($payload['start_at']) ? Carbon::parse($payload['start_at']) : $reservation->start_at;
        $endAt = isset($payload['end_at']) ? Carbon::parse($payload['end_at']) : $reservation->end_at;

        $oldStatus = $reservation->status;

        DB::transaction(function () use ($reservation, $payload, $startAt, $endAt, $availabilityService): void {
            $nextStatus = $payload['status'] ?? $reservation->status;

            if ($nextStatus !== 'cancelled') {
                $scheduleConflict = $availabilityService->checkOfficialScheduleConflict(
                    (int) $reservation->classroom_id,
                    $startAt,
                    $endAt,
                    null,
                    true
                );

                if ($scheduleConflict['has_conflict']) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Room is occupied by official schedule at selected time.',
                        'errors' => [
                            'classroom_id' => ['Room is occupied by official schedule at selected time.'],
                        ],
                        'conflicts' => $scheduleConflict['conflicts'],
                    ], 422));
                }

                $reservationConflict = $availabilityService->checkReservationConflict(
                    (int) $reservation->classroom_id,
                    $startAt,
                    $endAt,
                    (int) $reservation->id,
                    true
                );

                if ($reservationConflict['has_conflict']) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Room is already reserved at selected time.',
                        'errors' => [
                            'classroom_id' => ['Room is already reserved at selected time.'],
                        ],
                        'conflicts' => $reservationConflict['conflicts'],
                    ], 422));
                }
            }

            $reservation->update([
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => $nextStatus,
                'notes' => $payload['notes'] ?? $reservation->notes,
                'cancelled_at' => $nextStatus === 'cancelled' ? now() : null,
            ]);
        });

        $reservation->refresh();
        $reservation->load(['classroom', 'user']);

        return response()->json([
            'message' => 'Reservation updated successfully.',
            'data' => $reservation->fresh(['classroom', 'user']),
        ]);
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        $this->authorize('delete', $reservation);

        DB::transaction(function () use ($reservation): void {
            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        });

        $reservation->refresh();
        $reservation->load(['classroom', 'user']);

        return response()->json([
            'message' => 'Reservation cancelled successfully.',
            'data' => $reservation->fresh(['classroom', 'user']),
        ]);
    }

    public function checkAccess(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        // Debug log — shows incoming rfid_uid when ESP32 calls this endpoint
        \Log::debug('reservations/check called', [
            'user_id' => $request->input('user_id'),
            'classroom_id' => $request->input('classroom_id'),
            'rfid_uid' => $request->input('rfid_uid', '(none)'),
            'server_time' => now()->toIso8601String(),
        ]);
        $request->validate([
            'user_id'      => ['required', 'integer', 'exists:users,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
        ]);

        // App timezone is Asia/Manila — now() is already in Manila time
        $now = now();
        $graceMinutes = 0;
        $userId = $request->integer('user_id');
        $classroomId = $request->integer('classroom_id');

        // If an access card id or rfid uid is supplied, ensure it belongs to the claimed user.
        // This prevents callers from passing someone else's user_id to gain access.
        $hasCardInfo = $request->filled('access_card_id') || $request->filled('rfid_uid');

        if ($hasCardInfo) {
            $accessCardId = $request->integer('access_card_id') ?: null;
            $rfidUid = $request->input('rfid_uid') ?: null;

            $cardQuery = AccessCard::query();
            if ($accessCardId) {
                $cardQuery->whereKey($accessCardId);
            } elseif ($rfidUid) {
                $rawUid = strtolower(trim((string) $rfidUid));
                $normalizedUid = preg_replace('/^rfid[-_]?/', '', $rawUid) ?? $rawUid;
                $compactUid = preg_replace('/[^a-z0-9]/', '', $normalizedUid) ?? $normalizedUid;
                $colonUid = implode(':', str_split($compactUid, 2));
                $hyphenUid = implode('-', str_split($compactUid, 2));
                $cardQuery->whereIn('rfid_uid', array_unique([
                    $rawUid,
                    strtoupper($rawUid),
                    $normalizedUid,
                    strtoupper($normalizedUid),
                    $compactUid,
                    strtoupper($compactUid),
                    $colonUid,
                    strtoupper($colonUid),
                    $hyphenUid,
                    strtoupper($hyphenUid),
                    'RFID-'.$normalizedUid,
                    'RFID-'.strtoupper($normalizedUid),
                    'RFID-'.$colonUid,
                    'RFID-'.strtoupper($colonUid),
                ]));
            }

            $card = $cardQuery->first();

            if (! $card) {
                return response()->json([
                    'allowed' => false,
                    'message' => 'Access denied',
                    'reason'  => 'Access card not recognized',
                ], 403);
            }

            if ((int) $card->user_id !== (int) $userId) {
                return response()->json([
                    'allowed' => false,
                    'message' => 'Access denied',
                    'reason'  => 'Scanned card does not belong to the claimed user',
                ], 403);
            }
        }

        // Check for active reservation within grace period (before start + grace period, and after end)
        $reservation = Reservation::where('user_id', $userId)
            ->where('classroom_id', $classroomId)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('start_at', '<=', $now->copy()->addMinutes($graceMinutes))
            ->where('end_at', '>=', $now)
            ->first();

        if ($reservation) {
            return response()->json([
                'allowed'        => true,
                'message'        => 'Access granted',
                'reservation_id' => $reservation->id,
                'reservation'    => $reservation,
                'server_time'    => $now->toIso8601String(),
            ], 200);
        }

        $officialSchedule = Schedule::query()
            ->where('classroom_id', $classroomId)
            ->where('status', 'scheduled')
            ->where('day_of_week', $now->dayOfWeek)
            ->whereTime('start_at', '<=', $now->format('H:i:s'))
            ->whereTime('end_at', '>=', $now->format('H:i:s'))
            ->whereHas('course', function ($courseQuery) use ($userId): void {
                $courseQuery->where('instructor_user_id', $userId);
            })
            ->with('course')
            ->first();

        if ($officialSchedule) {
            return response()->json([
                'allowed' => true,
                'message' => 'Access granted during official class schedule',
                'schedule_id' => $officialSchedule->id,
                'schedule' => $officialSchedule,
                'server_time' => $now->toIso8601String(),
            ], 200);
        }

        // Determine why access was denied
        $anyReservation = Reservation::where('user_id', $userId)
            ->where('classroom_id', $classroomId)
            ->first();

        if (!$anyReservation) {
            return response()->json([
                'allowed' => false,
                'message' => 'No schedule',
                'reason'  => 'No active reservation or class schedule is valid at this time.',
                'server_time' => $now->toIso8601String(),
            ], 200);
        } elseif (!in_array($anyReservation->status, ['reserved', 'approved'])) {
            $reason = "Reservation status is '{$anyReservation->status}', not active.";
        } elseif ($now->isBefore($anyReservation->start_at->copy()->subMinutes($graceMinutes))) {
            $reason = 'Access reserved for: ' . $anyReservation->start_at->format('Y-m-d H:i');
        } else {
            $reason = 'Reservation time has expired. Reserved until: ' . $anyReservation->end_at->format('Y-m-d H:i');
        }

        return response()->json([
            'allowed'     => false,
            'message'     => 'Access denied',
            'reason'      => $reason,
            'server_time' => $now->toIso8601String(),
        ], 403);
    }
}