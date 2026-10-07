<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Support\RoomAvailabilityStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class RoomMapService
{
    public function __construct(
        private RoomAvailabilityService $availabilityService
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function mapBuildingsWithCoordinates(Collection $classrooms, CarbonInterface $startAt, CarbonInterface $endAt, ?CarbonInterface $now = null): Collection
    {
        $statuses = $this->availabilityService->buildRoomStatuses($classrooms, $startAt, $endAt, $now)->keyBy('classroom_id');

        return $classrooms
            ->groupBy(fn (Classroom $classroom): string => (string) $classroom->building)
            ->values()
            ->map(function (Collection $items, int $index) use ($statuses): array {
                $availableCount = $items->filter(function (Classroom $classroom) use ($statuses): bool {
                    return (string) ($statuses->get($classroom->id)['status'] ?? RoomAvailabilityStatus::AVAILABLE) === RoomAvailabilityStatus::AVAILABLE;
                })->count();

                return [
                    'building' => (string) $items->first()?->building,
                    'available' => $availableCount,
                    'is_full' => $availableCount === 0,
                    'coordinates' => $this->buildingCoordinates((string) $items->first()?->building, $index),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function roomsByBuilding(Collection $classrooms, string $building, CarbonInterface $startAt, CarbonInterface $endAt, ?CarbonInterface $now = null): Collection
    {
        $rooms = $classrooms->filter(fn (Classroom $classroom): bool => strcasecmp((string) $classroom->building, trim($building)) === 0)->values();
        $statusMap = $this->availabilityService->buildRoomStatuses($rooms, $startAt, $endAt, $now)->keyBy('classroom_id');

        return $rooms->map(function (Classroom $classroom) use ($statusMap): array {
            $status = $statusMap->get($classroom->id, [
                'status' => RoomAvailabilityStatus::AVAILABLE,
                'status_label' => 'Available',
                'time_info' => 'Available all day',
            ]);

            return [
                'id' => $classroom->id,
                'name' => (string) $classroom->name,
                'building' => (string) $classroom->building,
                'floor' => (string) ($classroom->floor ?? ''),
                'capacity' => (int) ($classroom->capacity ?? 0),
                'status' => (string) ($status['status'] ?? RoomAvailabilityStatus::AVAILABLE),
                'status_label' => (string) ($status['status_label'] ?? 'Available'),
                'time_info' => (string) ($status['time_info'] ?? 'Available all day'),
            ];
        })->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function fixedSchedulesByRoom(
        int $classroomId,
        CarbonInterface $rangeStart,
        CarbonInterface $rangeEnd,
        bool $recurringWeek = false
    ): Collection {
        return $this->availabilityService->itScopedSchedules()
            ->where('classroom_id', $classroomId)
            ->with(['course.instructor', 'courseOffering.instructor'])
            ->when(! $recurringWeek, function ($query) use ($rangeStart, $rangeEnd): void {
                $query
                    ->where('start_at', '<', $rangeEnd)
                    ->where('end_at', '>', $rangeStart);
            })
            ->orderBy('start_at')
            ->get()
            ->map(fn (Schedule $schedule): array => [
                'id' => $schedule->id,
                'start_at' => optional($schedule->start_at)->toIso8601String(),
                'end_at' => optional($schedule->end_at)->toIso8601String(),
                'day_of_week' => (int) $schedule->day_of_week,
                'start_time' => optional($schedule->start_at)->format('H:i'),
                'end_time' => optional($schedule->end_at)->format('H:i'),
                'status' => (string) ($schedule->status ?? 'scheduled'),
                'course_code' => (string) ($schedule->course?->code ?? ''),
                'course_title' => (string) ($schedule->course?->title ?? 'Scheduled class'),
            ])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function roomCurrentStatus(int $classroomId, ?CarbonInterface $at = null): array
    {
        $at ??= now();
        $classroom = Classroom::query()->find($classroomId);
        $currentSchedule = $this->availabilityService->itScopedSchedules()->where('classroom_id', $classroomId)->where('start_at', '<=', $at)->where('end_at', '>', $at)->orderBy('start_at')->first();
        $currentReservation = $this->availabilityService->itScopedReservations()->where('classroom_id', $classroomId)->where('start_at', '<=', $at)->where('end_at', '>', $at)->orderBy('start_at')->first();
        $nextSchedule = $this->availabilityService->itScopedSchedules()->where('classroom_id', $classroomId)->where('start_at', '>', $at)->orderBy('start_at')->first();

        if ($currentSchedule) {
            return [
                'status' => RoomAvailabilityStatus::OCCUPIED,
                'status_label' => 'Occupied',
                'source' => 'official_schedule',
                'current' => [
                    'type' => 'official_schedule',
                    'id' => $currentSchedule->id,
                    'start_at' => optional($currentSchedule->start_at)->toIso8601String(),
                    'end_at' => optional($currentSchedule->end_at)->toIso8601String(),
                ],
                'next_schedule' => $this->formatNextSchedule($nextSchedule),
            ];
        }

        if ($currentReservation) {
            return [
                'status' => RoomAvailabilityStatus::RESERVED,
                'status_label' => 'Reserved',
                'source' => 'reservation',
                'current' => [
                    'type' => 'reservation',
                    'id' => $currentReservation->id,
                    'start_at' => optional($currentReservation->start_at)->toIso8601String(),
                    'end_at' => optional($currentReservation->end_at)->toIso8601String(),
                ],
                'next_schedule' => $this->formatNextSchedule($nextSchedule),
            ];
        }

        if ($classroom && (int) $classroom->current_occupancy > 0) {
            return [
                'status' => RoomAvailabilityStatus::OCCUPIED,
                'status_label' => 'Occupied',
                'source' => 'live_occupancy',
                'reason' => 'Room is currently occupied.',
                'current' => [
                    'type' => 'live_occupancy',
                    'current_occupancy' => (int) $classroom->current_occupancy,
                    'capacity' => (int) $classroom->capacity,
                ],
                'next_schedule' => $this->formatNextSchedule($nextSchedule),
            ];
        }

        return [
            'status' => RoomAvailabilityStatus::AVAILABLE,
            'status_label' => 'Available',
            'source' => null,
            'current' => null,
            'next_schedule' => $this->formatNextSchedule($nextSchedule),
        ];
    }

    private function buildingCoordinates(string $building, int $index): array
    {
        $lower = strtolower($building);

        if (str_contains($lower, 'main')) {
            return ['lat' => 16.0, 'lng' => 120.0];
        }
        if (str_contains($lower, 'tech')) {
            return ['lat' => 16.0008, 'lng' => 120.0006];
        }
        if (str_contains($lower, 'science')) {
            return ['lat' => 16.0004, 'lng' => 120.0012];
        }

        return [
            ['lat' => 16.0, 'lng' => 120.0],
            ['lat' => 16.0008, 'lng' => 120.0006],
            ['lat' => 16.0004, 'lng' => 120.0012],
            ['lat' => 15.9995, 'lng' => 120.0002],
        ][$index % 4];
    }

    private function formatNextSchedule(?Schedule $schedule): ?array
    {
        if (! $schedule) {
            return null;
        }

        return [
            'id' => $schedule->id,
            'start_at' => optional($schedule->start_at)->toIso8601String(),
            'end_at' => optional($schedule->end_at)->toIso8601String(),
            'status' => (string) ($schedule->status ?? 'scheduled'),
        ];
    }
}
