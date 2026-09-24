<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MapFixedScheduleRequest;
use App\Http\Requests\Api\MapRoomStatusRequest;
use App\Models\Classroom;
use App\Models\Reservation;
use App\Services\RoomAvailabilityService;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapInteractionController extends Controller
{
    public function buildings(RoomAvailabilityService $availabilityService): JsonResponse
    {
        $classrooms = $this->itScopedClassrooms()->orderBy('building')->orderBy('name')->get();
        $now = now();

        $buildings = $availabilityService->mapBuildingsWithCoordinates(
            $classrooms,
            $now->copy(),
            $now->copy()->addHour(),
            $now
        );

        return response()->json([
            'data' => $buildings->values(),
        ]);
    }

    public function roomsByBuilding(string $building, RoomAvailabilityService $availabilityService): JsonResponse
    {
        $classrooms = $this->itScopedClassrooms()->orderBy('building')->orderBy('name')->get();
        $now = now();

        $rooms = $availabilityService->roomsByBuilding(
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
        Request $webRequest
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

        $schedules = $availabilityService->fixedSchedulesByRoom($classroom->id, $rangeStart, $rangeEnd);
        $reservations = Reservation::query()
            ->with('user')
            ->where('classroom_id', $classroom->id)
            ->whereIn('status', ['reserved', 'approved'])
            ->where('start_at', '<', $rangeEnd)
            ->where('end_at', '>', $rangeStart)
            ->orderBy('start_at')
            ->get()
            ->map(fn (Reservation $reservation): array => [
                'id' => $reservation->id,
                'status' => $reservation->status,
                'reserved_by' => (string) ($reservation->user?->name ?? 'Faculty'),
                'is_mine' => (int) $reservation->user_id === (int) $webRequest->user()?->id,
                'start_at' => optional($reservation->start_at)->toIso8601String(),
                'end_at' => optional($reservation->end_at)->toIso8601String(),
                'notes' => $reservation->notes,
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
        RoomAvailabilityService $availabilityService
    ): JsonResponse {
        if (! $this->isItScopedClassroom($classroom->id)) {
            abort(404);
        }

        $validated = $request->validated();
        $at = isset($validated['at']) ? Carbon::parse((string) $validated['at']) : now();

        $status = $availabilityService->roomCurrentStatus($classroom->id, $at);

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
        return Classroom::query()->where(function (Builder $query): void {
            $query->whereHas('schedules.course.instructor', function (Builder $scope): void {
                $this->applyItDepartmentScope($scope);
            })->orWhereHas('reservations.user', function (Builder $scope): void {
                $this->applyItDepartmentScope($scope);
            });
        });
    }

    private function applyItDepartmentScope(Builder $query): void
    {
        $query->where(function (Builder $scope): void {
            $scope->whereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%it%'])
                ->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%cit%'])
                ->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%cite%'])
                ->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%information technology%']);
        });
    }

    private function isItScopedClassroom(int $classroomId): bool
    {
        return $this->itScopedClassrooms()
            ->where('classrooms.id', $classroomId)
            ->exists();
    }
}
