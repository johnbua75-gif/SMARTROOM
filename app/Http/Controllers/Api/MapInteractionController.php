<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MapFixedScheduleRequest;
use App\Http\Requests\Api\MapRoomStatusRequest;
use App\Models\Classroom;
use App\Models\Reservation;
use App\Services\RoomAvailabilityService;
use App\Services\RoomMapService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class MapInteractionController extends Controller
{
    public function buildings(RoomMapService $roomMapService): JsonResponse
    {
        $buildings = Cache::remember('api:map:buildings:v1', now()->addSeconds(15), function () use ($roomMapService): array {
            $classrooms = $this->itScopedClassrooms()->orderBy('building')->orderBy('name')->get();
            $now = now();

            return $roomMapService->mapBuildingsWithCoordinates(
                $classrooms,
                $now->copy(),
                $now->copy()->addHour(),
                $now
            )->values()->all();
        });

        return response()->json([
            'data' => $buildings,
        ]);
    }

    public function roomsByBuilding(string $building, RoomMapService $roomMapService): JsonResponse
    {
        $classrooms = $this->itScopedClassrooms()->orderBy('building')->orderBy('name')->get();
        $now = now();

        $rooms = $roomMapService->roomsByBuilding(
            $classrooms,
            urldecode($building),
            $now->copy(),
            $now->copy()->addHour(),
            $now
        );

        return response()->json([
            'data' => $rooms,
            'meta' => [
                'building' => urldecode($building),
            ],
        ]);
    }

    public function fixedSchedulesByRoom(
        MapFixedScheduleRequest $request,
        Classroom $classroom,
        RoomAvailabilityService $availabilityService,
        RoomMapService $roomMapService
    ): JsonResponse {
        if (! $this->isItScopedClassroom($classroom->id)) {
            abort(404);
        }

        $validated = $request->validated();

        if (isset($validated['week_start'])) {
            $rangeStart = Carbon::parse((string) $validated['week_start'])->startOfWeek();
            $rangeEnd = $rangeStart->copy()->endOfWeek();
            $mode = 'week';
        } else {
            $rangeStart = isset($validated['date'])
                ? Carbon::parse((string) $validated['date'])->startOfDay()
                : now()->startOfDay();
            $rangeEnd = $rangeStart->copy()->endOfDay();
            $mode = 'day';
        }

        $schedules = $roomMapService->fixedSchedulesByRoom(
            $classroom->id,
            $rangeStart,
            $rangeEnd,
            $mode === 'week'
        );
        $reservations = $availabilityService->itScopedReservations()
            ->where('classroom_id', $classroom->id)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('end_at', '>', now())
            ->where('start_at', '<', $rangeEnd)
            ->where('end_at', '>', $rangeStart)
            ->orderBy('start_at')
            ->get()
            ->map(fn (Reservation $reservation): array => [
                'id' => $reservation->id,
                'status' => $reservation->status,
                'start_at' => optional($reservation->start_at)->toIso8601String(),
                'end_at' => optional($reservation->end_at)->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'data' => [
                'classroom_id' => $classroom->id,
                'classroom_name' => $classroom->name,
                'mode' => $mode,
                'range_start' => $rangeStart->toIso8601String(),
                'range_end' => $rangeEnd->toIso8601String(),
                'schedules' => $schedules,
                'reservations' => $reservations,
            ],
        ]);
    }

    public function roomStatus(
        MapRoomStatusRequest $request,
        Classroom $classroom,
        RoomMapService $roomMapService
    ): JsonResponse {
        if (! $this->isItScopedClassroom($classroom->id)) {
            abort(404);
        }

        $validated = $request->validated();
        $at = isset($validated['at']) ? Carbon::parse((string) $validated['at']) : now();

        $status = $roomMapService->roomCurrentStatus($classroom->id, $at);

        return response()->json([
            'data' => [
                'classroom_id' => $classroom->id,
                'classroom_name' => $classroom->name,
                'at' => $at->toIso8601String(),
                ...$status,
            ],
        ]);
    }

    private function itScopedClassrooms(): Builder
    {
        return app(RoomAvailabilityService::class)->itScopedClassrooms();
    }

    private function isItScopedClassroom(int $classroomId): bool
    {
        return $this->itScopedClassrooms()->whereKey($classroomId)->exists();
    }
}
