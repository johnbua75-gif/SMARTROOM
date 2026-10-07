<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckRoomAvailabilityRequest;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Services\RoomAvailabilityService;
use App\Support\RoomAvailabilityStatus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RoomAvailabilityController extends Controller
{
    public function check(CheckRoomAvailabilityRequest $request, RoomAvailabilityService $availabilityService): JsonResponse
    {
        $payload = $request->validated();

        $startAt = Carbon::parse($payload['start_at']);
        $endAt = Carbon::parse($payload['end_at']);

        $availability = $availabilityService->checkAvailability(
            (int) $payload['classroom_id'],
            $startAt,
            $endAt
        );

        $cancelledClasses = $availabilityService->itScopedCancelledSchedules()
            ->where('classroom_id', (int) $payload['classroom_id'])
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->orderBy('start_at')
            ->get()
            ->map(function (Schedule $schedule): array {
                return [
                    'schedule_id' => $schedule->id,
                    'start_at' => optional($schedule->start_at)->toIso8601String(),
                    'end_at' => optional($schedule->end_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'classroom_id' => (int) $payload['classroom_id'],
                'start_at' => $startAt->toIso8601String(),
                'end_at' => $endAt->toIso8601String(),
                'cancelled_classes' => $cancelledClasses,
                ...$availability,
            ],
        ]);
    }

    public function statuses(Request $request, RoomAvailabilityService $availabilityService): JsonResponse
    {
        $validated = $request->validate([
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
        ]);

        $startAt = isset($validated['start_at']) ? Carbon::parse($validated['start_at']) : now();
        $endAt = isset($validated['end_at']) ? Carbon::parse($validated['end_at']) : $startAt->copy()->addHour();

        if ($endAt->lte($startAt)) {
            return response()->json([
                'message' => 'The end at must be a date after start at.',
                'errors' => [
                    'end_at' => ['The end at must be a date after start at.'],
                ],
            ], 422);
        }

        $cacheInput = implode('|', [
            $validated['start_at'] ?? 'current',
            $validated['end_at'] ?? 'next-hour',
        ]);
        $responseData = Cache::remember('api:room-statuses:v1:'.hash('sha256', $cacheInput), now()->addSeconds(15), function () use ($availabilityService, $startAt, $endAt): array {
            $classrooms = $availabilityService->itScopedClassrooms()
                ->orderBy('building')
                ->orderBy('name')
                ->get();

            $statuses = $availabilityService->buildRoomStatuses($classrooms, $startAt, $endAt, now())
                ->keyBy('classroom_id');

            $data = $classrooms->map(function (Classroom $classroom) use ($statuses): array {
                $status = $statuses->get($classroom->id, [
                    'status' => RoomAvailabilityStatus::AVAILABLE,
                    'status_label' => 'Available',
                    'time_info' => 'Available all day',
                    'reason' => null,
                    'conflicts' => [],
                ]);

                return [
                    'classroom_id' => $classroom->id,
                    'name' => $classroom->name,
                    'building' => $classroom->building,
                    'floor' => $classroom->floor,
                    'status' => $status['status'],
                    'status_label' => $status['status_label'],
                    'time_info' => $status['time_info'],
                    'reason' => $status['reason'],
                    'conflicts' => $status['conflicts'],
                ];
            })->values();

            return [
                'data' => $data->all(),
                'meta' => [
                    'start_at' => $startAt->toIso8601String(),
                    'end_at' => $endAt->toIso8601String(),
                ],
            ];
        });

        return response()->json($responseData);
    }
}
