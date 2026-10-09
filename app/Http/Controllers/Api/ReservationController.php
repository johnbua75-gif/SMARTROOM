<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckAccessReservationRequest;
use App\Http\Requests\Api\StoreReservationRequest;
use App\Http\Requests\Api\UpdateReservationRequest;
use App\Models\AccessCard;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\User;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request, RoomAvailabilityService $availabilityService): JsonResponse
    {
        $this->authorize('create', Reservation::class);

        $payload = $request->validated();
        // App timezone is Asia/Manila - parse directly, no conversion needed
        $startAt = Carbon::parse($payload['start_at']);
        $endAt = Carbon::parse($payload['end_at']);

        $reservation = DB::transaction(function () use ($request, $payload, $startAt, $endAt, $availabilityService): Reservation {
            $classroom = $availabilityService->lockClassroomForUpdate((int) $payload['classroom_id']);
            $unavailability = $availabilityService->classroomUnavailability($classroom);

            if ($unavailability) {
                throw new HttpResponseException(response()->json([
                    'message' => $unavailability['reason'],
                    'errors' => [
                        'classroom_id' => [$unavailability['reason']],
                    ],
                ], 422));
            }

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

            $reservation = Reservation::create([
                'classroom_id' => $payload['classroom_id'],
                'user_id' => $request->user()->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => 'approved',
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

        // App timezone is Asia/Manila - parse directly, no conversion needed
        $startAt = isset($payload['start_at']) ? Carbon::parse($payload['start_at']) : $reservation->start_at;
        $endAt = isset($payload['end_at']) ? Carbon::parse($payload['end_at']) : $reservation->end_at;

        $oldStatus = $reservation->status;

        DB::transaction(function () use ($reservation, $payload, $startAt, $endAt, $availabilityService): void {
            $nextStatus = $payload['status'] ?? $reservation->status;

            if ($nextStatus !== 'cancelled') {
                $classroom = $availabilityService->lockClassroomForUpdate((int) $reservation->classroom_id);
                $unavailability = $availabilityService->classroomUnavailability($classroom);

                if ($unavailability) {
                    throw new HttpResponseException(response()->json([
                        'message' => $unavailability['reason'],
                        'errors' => [
                            'classroom_id' => [$unavailability['reason']],
                        ],
                    ], 422));
                }

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

    public function checkAccess(CheckAccessReservationRequest $request): JsonResponse
    {
        $request->validated();

        $now = now();
        $userId = $request->integer('user_id');
        $device = $request->attributes->get('device');
        $classroomId = $device
            ? (int) $device->classroom_id
            : $request->integer('classroom_id');

        $person = User::query()->find($userId);
        if (! $person || $person->status !== 'active') {
            return $this->accessDecision($request, false, 'Access denied', 'User account is inactive');
        }

        if ($device && $request->filled('classroom_id') && (int) $device->classroom_id !== $request->integer('classroom_id')) {
            return $this->accessDecision($request, false, 'Access denied', 'Device is not registered for this classroom');
        }

        $hasCardInfo = $request->filled('access_card_id') || $request->filled('rfid_uid');

        if ($hasCardInfo) {
            $accessCardId = $request->integer('access_card_id') ?: null;
            $rfidUid = $request->input('rfid_uid') ?: null;

            $cardQuery = AccessCard::query();
            if ($accessCardId) {
                $cardQuery->whereKey($accessCardId);
            } elseif ($rfidUid) {
                $cardQuery->whereNormalizedRfidUid((string) $rfidUid);
            }

            $card = $cardQuery->first();

            if (! $card) {
                return $this->accessDecision($request, false, 'Access denied', 'Access card not recognized');
            }

            if (
                (string) $card->status !== 'active'
                || ($card->expires_at && $card->expires_at->isBefore(today()))
            ) {
                return $this->accessDecision($request, false, 'Access denied', 'Access card is inactive or expired');
            }

            if ($card->user()->where('status', 'active')->doesntExist()) {
                return $this->accessDecision($request, false, 'Access denied', 'Cardholder account is inactive');
            }

            if ((int) $card->user_id !== (int) $userId) {
                return $this->accessDecision($request, false, 'Access denied', 'Scanned card does not belong to the claimed user');
            }
        }

        $reservation = Reservation::where('user_id', $userId)
            ->where('classroom_id', $classroomId)
            ->where('status', 'approved')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();

        if ($reservation) {
            return $this->accessDecision(
                $request,
                true,
                'Access granted',
                '',
                ['reservation_id' => $reservation->id, 'reservation' => $reservation]
            );
        }

        $officialSchedule = Schedule::query()
            ->where('classroom_id', $classroomId)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->where('day_of_week', $now->dayOfWeek)
            ->whereTime('start_at', '<=', $now->format('H:i:s'))
            ->whereTime('end_at', '>=', $now->format('H:i:s'))
            ->forInstructor($userId)
            ->with(['course', 'courseOffering.instructor'])
            ->first();

        if ($officialSchedule) {
            return $this->accessDecision(
                $request,
                true,
                'Access granted during official class schedule',
                '',
                ['schedule_id' => $officialSchedule->id, 'schedule' => $officialSchedule]
            );
        }

        $anyReservation = Reservation::where('user_id', $userId)
            ->where('classroom_id', $classroomId)
            ->first();

        if (! $anyReservation) {
            return $this->accessDecision(
                $request,
                false,
                'No schedule',
                'No active reservation or class schedule is valid at this time.',
                [],
                200
            );
        }

        if ($anyReservation->status !== 'approved') {
            $reason = "Reservation status is '{$anyReservation->status}', not approved.";
        } elseif ($now->isBefore($anyReservation->start_at)) {
            $reason = 'Access reserved for: '.$anyReservation->start_at->format('Y-m-d H:i');
        } else {
            $reason = 'Reservation time has expired. Reserved until: '.$anyReservation->end_at->format('Y-m-d H:i');
        }

        return $this->accessDecision($request, false, 'Access denied', $reason);
    }

    private function accessDecision(
        CheckAccessReservationRequest $request,
        bool $allowed,
        string $message,
        string $reason,
        array $legacyData = [],
        int $legacyStatus = 403
    ): JsonResponse {
        if (! $allowed) {
            $this->logDeniedDoorAccess($request, $reason !== '' ? $reason : $message);
        }

        $response = [
            'allowed' => $allowed,
            'message' => $message,
            'reason' => $reason,
        ];
        $status = 200;

        if (! $request->attributes->get('door_api', false)) {
            $response = [...$response, ...$legacyData, 'server_time' => now()->toIso8601String()];
            $status = $allowed ? 200 : $legacyStatus;
        }

        return response()->json($response, $status);
    }

    private function logDeniedDoorAccess(CheckAccessReservationRequest $request, string $reason): void
    {
        $device = $request->attributes->get('device');
        $normalizedUid = AccessCard::normalizeRfidUid((string) $request->input('rfid_uid', ''));

        Log::warning('Door access denied', [
            'user_id' => $request->integer('user_id'),
            'classroom_id' => $device?->classroom_id ?? $request->integer('classroom_id'),
            'rfid_uid_suffix' => $normalizedUid === '' ? null : substr($normalizedUid, -4),
            'server_time' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
            'reason' => $reason,
        ]);
    }
}
