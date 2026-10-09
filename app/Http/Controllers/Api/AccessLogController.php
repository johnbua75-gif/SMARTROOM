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
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        $device = $request->attributes->get('device');
        $doorApi = $request->attributes->get('door_api', false);
        $isDeviceRequest = $device || $doorApi;
        $classroomId = $device
            ? (int) $device->classroom_id
            : (int) ($validated['classroom_id'] ?? 0);
        $validated['classroom_id'] = $classroomId;

        if ($device && $request->filled('classroom_id') && (int) $device->classroom_id !== (int) $request->input('classroom_id')) {
            abort(403, 'Device is not registered for this classroom.');
        }
        $now = now();

        // If a card id or rfid uid is present in the payload, ensure the card belongs to the claimed user.
        $card = null;
        if (! empty($validated['access_card_id'])) {
            $card = AccessCard::find($validated['access_card_id']);
        }
        if (! $card && data_get($validated, 'metadata.rfid_uid')) {
            $card = AccessCard::query()
                ->whereNormalizedRfidUid((string) data_get($validated, 'metadata.rfid_uid'))
                ->first();
        }

        if ($card) {
            $claimedUserId = $validated['user_id'] ?? null;
            if ($claimedUserId !== null && (int) $card->user_id !== (int) $claimedUserId) {
                $validated['result'] = 'denied';
                $validated['reason'] = $validated['reason'] ?? 'Scanned card does not belong to the claimed user';
            }

            $validated['access_card_id'] ??= $card->id;
            $validated['user_id'] = $card->user_id;
            $userId = $card->user_id;
        }

        if (
            $card
            && ((string) $card->status !== 'active' || ($card->expires_at && $card->expires_at->isBefore(today())))
        ) {
            $validated['result'] = 'denied';
            $validated['reason'] = $validated['reason'] ?? 'Access card is inactive or expired';
        }

        $method = strtoupper((string) data_get($validated, 'metadata.method', ''));
        $isRfidLog = $method === 'RFID'
            || ! empty($validated['access_card_id'])
            || ! empty(data_get($validated, 'metadata.rfid_uid'));
        if ($isDeviceRequest && $validated['result'] === 'granted' && $isRfidLog && ! $card) {
            $validated['result'] = 'denied';
            $validated['reason'] = $validated['reason'] ?? 'Device grant did not include a recognized access card';
        }

        if ($card && $card->user()->where('status', 'active')->doesntExist()) {
            $validated['result'] = 'denied';
            $validated['reason'] = $validated['reason'] ?? 'Cardholder account is inactive';
        }

        $accessedAt = isset($validated['accessed_at'])
            ? Carbon::parse($validated['accessed_at'])->setTimezone(config('app.timezone'))
            : now();
        $validated['accessed_at'] = $accessedAt;

        // A granted event must correspond to either an active reservation or an official class.
        if ($userId && $classroomId) {
            $reservation = Reservation::where('user_id', $userId)
                ->where('classroom_id', $classroomId)
                ->where('status', 'approved')
                ->where('start_at', '<=', $accessedAt)
                ->where('end_at', '>=', $accessedAt)
                ->first();

            $officialSchedule = Schedule::query()
                ->where('classroom_id', $classroomId)
                ->whereIn('status', ['scheduled', 'ongoing'])
                ->where('day_of_week', $accessedAt->dayOfWeek)
                ->whereTime('start_at', '<=', $accessedAt->format('H:i:s'))
                ->whereTime('end_at', '>=', $accessedAt->format('H:i:s'))
                ->forInstructor((int) $userId)
                ->exists();

            // Update result based on reservation validity
            if (! $reservation && ! $officialSchedule && $validated['result'] !== 'denied') {
                $validated['result'] = 'denied';
                $validated['reason'] = $validated['reason'] ?? 'No valid reservation or official class during access time';
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
            Cache::forget('faculty:notifications:v1:'.$log->user_id);
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
