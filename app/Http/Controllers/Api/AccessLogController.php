<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAccessLogRequest;
use App\Http\Requests\Api\UpdateAccessLogRequest;
use App\Http\Resources\AccessLogResource;
use App\Models\AccessCard;
use App\Models\AccessLog;
use App\Models\Notification;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AccessLog::query()->with(['accessCard', 'classroom', 'user']);

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->integer('classroom_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('result')) {
            $query->where('result', $request->string('result'));
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->string('direction'));
        }

        $logs = $query->latest('accessed_at')->paginate($request->integer('per_page', 50));

        return AccessLogResource::collection($logs);
    }

    public function store(StoreAccessLogRequest $request): AccessLogResource
    {
        $validated = $request->validated();
        $userId = $validated['user_id'] ?? null;
        $classroomId = $validated['classroom_id'];
        $now = now();

        // If a card id or rfid uid is present in the payload, ensure the card belongs to the claimed user.
        $card = null;
        if (! empty($validated['access_card_id'])) {
            $card = AccessCard::find($validated['access_card_id']);
        }
        if (! $card && ! empty($validated['metadata']['rfid_uid'])) {
            $rfidUid = strtolower(trim((string) $validated['metadata']['rfid_uid']));
            $rfidUid = preg_replace('/^rfid[-_]?/', '', $rfidUid) ?? $rfidUid;
            $compactUid = preg_replace('/[^a-z0-9]/', '', $rfidUid) ?? $rfidUid;
            $colonUid = implode(':', str_split($compactUid, 2));
            $hyphenUid = implode('-', str_split($compactUid, 2));
            $uidVariants = array_unique([
                $rfidUid,
                strtoupper($rfidUid),
                $compactUid,
                strtoupper($compactUid),
                $colonUid,
                strtoupper($colonUid),
                $hyphenUid,
                strtoupper($hyphenUid),
                'RFID-'.$rfidUid,
                'RFID-'.strtoupper($rfidUid),
                'RFID-'.$colonUid,
                'RFID-'.strtoupper($colonUid),
            ]);

            $card = AccessCard::query()->whereIn('rfid_uid', $uidVariants)->first();
        }

        if ($card) {
            $validated['access_card_id'] ??= $card->id;
            $validated['user_id'] ??= $card->user_id;
            $userId = $validated['user_id'];
        }

        if ($card && $userId && (int) $card->user_id !== (int) $userId) {
            $validated['result'] = 'denied';
            $validated['reason'] = $validated['reason'] ?? 'Scanned card does not belong to the claimed user';
        }

        // If user_id is provided, verify they have an approved reservation
        if ($userId && $classroomId) {
            $reservation = Reservation::where('user_id', $userId)
                ->where('classroom_id', $classroomId)
                ->where('status', 'approved')
                ->where('start_at', '<=', $now->copy()->addMinutes(10))
                ->where('end_at', '>=', $now)
                ->first();

            // Update result based on reservation validity
            if (! $reservation && $validated['result'] !== 'denied') {
                $validated['result'] = 'denied';
                $validated['reason'] = $validated['reason'] ?? 'No valid reservation during access time';
            }
        }

        $log = AccessLog::create($validated)->load(['accessCard', 'classroom', 'user']);

        if (
            $log->result === 'granted'
            && $log->user_id
            && strtolower((string) data_get($log->metadata, 'method')) === 'rfid'
        ) {
            Notification::create([
                'user_id' => $log->user_id,
                'type' => 'rfid_access_granted',
                'title' => 'RFID Access Granted',
                'body' => 'Your RFID card granted access to '.($log->classroom?->name ?? 'the assigned room').'.',
                'data' => [
                    'access_log_id' => $log->id,
                    'classroom_id' => $log->classroom_id,
                    'accessed_at' => $log->accessed_at?->toIso8601String(),
                    'method' => 'RFID',
                ],
            ]);
        }

        return new AccessLogResource($log);
    }

    public function show(AccessLog $accessLog): AccessLogResource
    {
        return new AccessLogResource($accessLog->load(['accessCard', 'classroom', 'user']));
    }

    public function update(UpdateAccessLogRequest $request, AccessLog $accessLog): AccessLogResource
    {
        $accessLog->update($request->validated());

        return new AccessLogResource($accessLog->fresh()->load(['accessCard', 'classroom', 'user']));
    }

    public function destroy(AccessLog $accessLog): JsonResponse
    {
        $accessLog->delete();

        return response()->json(['message' => 'Access log deleted successfully.']);
    }
}
