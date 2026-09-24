<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Support\DepartmentScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RoomAvailabilityService
{
    private const ACTIVE_SCHEDULE_STATUSES = ['scheduled', 'ongoing'];

    private const ACTIVE_RESERVATION_STATUSES = ['reserved', 'approved'];

    /**
     * @return array{available: bool, status: string, reason: string|null, conflicts: array<int, array<string, mixed>>}
     */
    public function checkAvailability(int $classroomId, CarbonInterface $startAt, CarbonInterface $endAt, ?int $ignoreReservationId = null): array
    {
        $classroom = Classroom::query()->find($classroomId);
        if ($classroom && in_array((string) $classroom->status, ['maintenance', 'unavailable'], true)) {
            return [
                'available' => false,
                'status' => 'maintenance',
                'reason' => $classroom->unavailable_reason ?: 'Room is temporarily unavailable due to an issue.',
                'conflicts' => [],
            ];
        }

        $scheduleConflicts = $this->scheduleConflicts($classroomId, $startAt, $endAt)->get();
        $reservationConflicts = $this->reservationConflicts($classroomId, $startAt, $endAt, $ignoreReservationId)->get();

        $conflicts = [];

        foreach ($scheduleConflicts as $schedule) {
            $conflicts[] = [
                'type' => 'schedule',
                'id' => $schedule->id,
                'status' => $schedule->status,
                'start_at' => optional($schedule->start_at)->toIso8601String(),
                'end_at' => optional($schedule->end_at)->toIso8601String(),
            ];
        }

        foreach ($reservationConflicts as $reservation) {
            $conflicts[] = [
                'type' => 'reservation',
                'id' => $reservation->id,
                'status' => $reservation->status,
                'start_at' => optional($reservation->start_at)->toIso8601String(),
                'end_at' => optional($reservation->end_at)->toIso8601String(),
            ];
        }

        if ($scheduleConflicts->isNotEmpty()) {
            return [
                'available' => false,
                'status' => 'occupied',
                'reason' => 'Room is occupied by official schedule at selected time.',
                'conflicts' => $conflicts,
            ];
        }

        if ($reservationConflicts->isNotEmpty()) {
            return [
                'available' => false,
                'status' => 'reserved',
                'reason' => 'Room is already reserved at selected time.',
                'conflicts' => $conflicts,
            ];
        }

        if ($classroom && (int) $classroom->current_occupancy > 0 && $startAt->lte(now()) && $endAt->gt(now())) {
            return [
                'available' => false,
                'status' => 'occupied',
                'reason' => 'Room is currently occupied.',
                'conflicts' => [],
            ];
        }

        return [
            'available' => true,
            'status' => 'available',
            'reason' => null,
            'conflicts' => [],
        ];
    }

    /**
     * @param Collection<int, Classroom> $classrooms
     * @return Collection<int, array<string, mixed>>
     */
    public function buildRoomStatuses(Collection $classrooms, CarbonInterface $startAt, CarbonInterface $endAt, ?CarbonInterface $now = null): Collection
    {
        $now ??= now();

        $classroomIds = $classrooms->pluck('id')->filter()->values();
        if ($classroomIds->isEmpty()) {
            return collect();
        }

        $schedules = Schedule::query()
            ->whereIn('classroom_id', $classroomIds)
            ->whereIn('status', self::ACTIVE_SCHEDULE_STATUSES)
            ->whereHas('course.instructor', function ($query): void {
                $this->applyItDepartmentScope($query);
            })
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->orderBy('start_at')
            ->get()
            ->groupBy('classroom_id');

        $reservations = Reservation::query()
            ->whereIn('classroom_id', $classroomIds)
            ->whereIn('status', self::ACTIVE_RESERVATION_STATUSES)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->orderBy('start_at')
            ->get()
            ->groupBy('classroom_id');

        return $classrooms->map(function (Classroom $classroom) use ($schedules, $reservations, $now): array {
            if (in_array((string) $classroom->status, ['maintenance', 'unavailable'], true)) {
                return [
                    'classroom_id' => $classroom->id,
                    'status' => 'maintenance',
                    'status_label' => 'Unavailable',
                    'time_info' => 'Blocked for issues',
                    'reason' => $classroom->unavailable_reason ?: 'Room is temporarily unavailable due to an issue.',
                    'conflicts' => [],
                ];
            }

            $roomSchedules = $schedules->get($classroom->id, collect());
            $roomReservations = $reservations->get($classroom->id, collect());

            $currentSchedule = $roomSchedules->first(function (Schedule $schedule) use ($now): bool {
                return $schedule->start_at !== null
                    && $schedule->end_at !== null
                    && $schedule->start_at->lte($now)
                    && $schedule->end_at->gt($now);
            });

            $currentReservation = $roomReservations->first(function (Reservation $reservation) use ($now): bool {
                return $reservation->start_at !== null
                    && $reservation->end_at !== null
                    && $reservation->start_at->lte($now)
                    && $reservation->end_at->gt($now);
            });

            $nextBlockingSchedule = $roomSchedules
                ->filter(fn (Schedule $schedule): bool => $schedule->start_at !== null && $schedule->start_at->gt($now))
                ->sortBy('start_at')
                ->first();

            $nextBlockingReservation = $roomReservations
                ->filter(fn (Reservation $reservation): bool => $reservation->start_at !== null && $reservation->start_at->gt($now))
                ->sortBy('start_at')
                ->first();

            $status = 'available';
            $statusLabel = 'Available';
            $reason = null;
            $timeInfo = 'Available all day';

            if ($currentSchedule) {
                $status = 'occupied';
                $statusLabel = 'Occupied';
                $reason = 'Room is occupied by official schedule at selected time.';

                if ($currentSchedule->end_at) {
                    $timeInfo = 'Free at '.$currentSchedule->end_at->format('g:i A');
                }
            } elseif ($currentReservation) {
                $status = 'reserved';
                $statusLabel = 'Reserved';
                $reason = 'Room is already reserved at selected time.';

                if ($currentReservation->end_at) {
                    $timeInfo = 'Free at '.$currentReservation->end_at->format('g:i A');
                }
            } elseif ((int) $classroom->current_occupancy > 0) {
                $status = 'occupied';
                $statusLabel = 'Occupied';
                $reason = 'Room is currently occupied.';
                $timeInfo = 'In use now';
            } else {
                $nextStartCandidates = collect([
                    optional($nextBlockingSchedule)->start_at,
                    optional($nextBlockingReservation)->start_at,
                ])->filter();

                $nearestNext = $nextStartCandidates->sort()->first();
                if ($nearestNext) {
                    $timeInfo = 'Until '.$nearestNext->format('g:i A');
                }
            }

            $conflicts = $roomSchedules->map(function (Schedule $schedule): array {
                return [
                    'type' => 'schedule',
                    'id' => $schedule->id,
                    'status' => $schedule->status,
                    'start_at' => optional($schedule->start_at)->toIso8601String(),
                    'end_at' => optional($schedule->end_at)->toIso8601String(),
                ];
            })->values();

            $reservationConflicts = $roomReservations->map(function (Reservation $reservation): array {
                return [
                    'type' => 'reservation',
                    'id' => $reservation->id,
                    'status' => $reservation->status,
                    'start_at' => optional($reservation->start_at)->toIso8601String(),
                    'end_at' => optional($reservation->end_at)->toIso8601String(),
                ];
            })->values();

            return [
                'classroom_id' => $classroom->id,
                'status' => $status,
                'status_label' => $statusLabel,
                'time_info' => $timeInfo,
                'reason' => $reason,
                'conflicts' => $conflicts->merge($reservationConflicts)->values()->all(),
            ];
        })->values();
    }

    /**
     * @return array{has_conflict: bool, message: string|null, conflicts: array<int, array<string, mixed>>}
     */
    public function checkOfficialScheduleConflict(
        int $classroomId,
        CarbonInterface $startAt,
        CarbonInterface $endAt,
        ?int $ignoreScheduleId = null,
        bool $forUpdateLock = false
    ): array {
        $query = $this->scheduleConflicts($classroomId, $startAt, $endAt, $ignoreScheduleId);

        if ($forUpdateLock) {
            $query->lockForUpdate();
        }

        $hasConflict = (clone $query)->exists();
        $conflicts = $hasConflict ? $query->get() : collect();

        return [
            'has_conflict' => $hasConflict,
            'message' => $hasConflict
                ? 'Official schedule conflict: room is already occupied by another official schedule at selected time.'
                : null,
            'conflicts' => $conflicts->map(fn (Schedule $schedule): array => [
                'type' => 'schedule',
                'id' => $schedule->id,
                'status' => $schedule->status,
                'start_at' => optional($schedule->start_at)->toIso8601String(),
                'end_at' => optional($schedule->end_at)->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array{has_conflict: bool, message: string|null, conflicts: array<int, array<string, mixed>>}
     */
    public function checkReservationConflict(
        int $classroomId,
        CarbonInterface $startAt,
        CarbonInterface $endAt,
        ?int $ignoreReservationId = null,
        bool $forUpdateLock = false
    ): array {
        $query = $this->reservationConflicts($classroomId, $startAt, $endAt, $ignoreReservationId);

        if ($forUpdateLock) {
            $query->lockForUpdate();
        }

        $hasConflict = (clone $query)->exists();
        $conflicts = $hasConflict ? $query->get() : collect();

        return [
            'has_conflict' => $hasConflict,
            'message' => $hasConflict ? 'Room is already reserved at selected time.' : null,
            'conflicts' => $conflicts->map(fn (Reservation $reservation): array => [
                'type' => 'reservation',
                'id' => $reservation->id,
                'status' => $reservation->status,
                'start_at' => optional($reservation->start_at)->toIso8601String(),
                'end_at' => optional($reservation->end_at)->toIso8601String(),
            ])->values()->all(),
        ];
    }

    private function scheduleConflicts(
        int $classroomId,
        CarbonInterface $startAt,
        CarbonInterface $endAt,
        ?int $ignoreScheduleId = null
    ) {
        return Schedule::query()
            ->where('classroom_id', $classroomId)
            ->whereIn('status', self::ACTIVE_SCHEDULE_STATUSES)
            ->when($ignoreScheduleId !== null, function ($query) use ($ignoreScheduleId): void {
                $query->where('id', '!=', $ignoreScheduleId);
            })
            ->whereHas('course.instructor', function ($query): void {
                $this->applyItDepartmentScope($query);
            })
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->orderBy('start_at');
    }

    private function reservationConflicts(
        int $classroomId,
        CarbonInterface $startAt,
        CarbonInterface $endAt,
        ?int $ignoreReservationId = null
    ) {
        return Reservation::query()
            ->where('classroom_id', $classroomId)
            ->whereIn('status', self::ACTIVE_RESERVATION_STATUSES)
            ->when($ignoreReservationId !== null, function ($query) use ($ignoreReservationId): void {
                $query->where('id', '!=', $ignoreReservationId);
            })
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->orderBy('start_at');
    }

    private function applyItDepartmentScope($query): void
    {
        $query->where(function ($scope): void {
            $scope->whereRaw('LOWER(COALESCE(department, \'\')) LIKE ?', ['%it%'])
                ->orWhereRaw('LOWER(COALESCE(department, \'\')) LIKE ?', ['%cit%'])
                ->orWhereRaw('LOWER(COALESCE(department, \'\')) LIKE ?', ['%cite%'])
                ->orWhereRaw('LOWER(COALESCE(department, \'\')) LIKE ?', ['%information technology%']);
        });
    }

    public function isItUserDepartment(?string $department): bool
    {
        return DepartmentScope::isItDepartment($department);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function mapBuildingsWithCoordinates(Collection $classrooms, CarbonInterface $startAt, CarbonInterface $endAt, ?CarbonInterface $now = null): Collection
    {
        $statuses = $this->buildRoomStatuses($classrooms, $startAt, $endAt, $now)->keyBy('classroom_id');

        return $classrooms
            ->groupBy(fn (Classroom $classroom): string => (string) $classroom->building)
            ->values()
            ->map(function (Collection $items, int $index) use ($statuses): array {
                $availableCount = $items->filter(function (Classroom $classroom) use ($statuses): bool {
                    return (string) ($statuses->get($classroom->id)['status'] ?? 'available') === 'available';
                })->count();

                $coordinates = $this->buildingCoordinates((string) $items->first()?->building, $index);

                return [
                    'building' => (string) $items->first()?->building,
                    'available' => $availableCount,
                    'is_full' => $availableCount === 0,
                    'coordinates' => $coordinates,
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function roomsByBuilding(Collection $classrooms, string $building, CarbonInterface $startAt, CarbonInterface $endAt, ?CarbonInterface $now = null): Collection
    {
        $normalizedBuilding = trim($building);

        $rooms = $classrooms->filter(function (Classroom $classroom) use ($normalizedBuilding): bool {
            return strcasecmp((string) $classroom->building, $normalizedBuilding) === 0;
        })->values();

        $statusMap = $this->buildRoomStatuses($rooms, $startAt, $endAt, $now)->keyBy('classroom_id');

        return $rooms->map(function (Classroom $classroom) use ($statusMap): array {
            $status = $statusMap->get($classroom->id, [
                'status' => 'available',
                'status_label' => 'Available',
                'time_info' => 'Available all day',
            ]);

            return [
                'id' => $classroom->id,
                'name' => (string) $classroom->name,
                'building' => (string) $classroom->building,
                'floor' => (string) ($classroom->floor ?? ''),
                'capacity' => (int) ($classroom->capacity ?? 0),
                'status' => (string) ($status['status'] ?? 'available'),
                'status_label' => (string) ($status['status_label'] ?? 'Available'),
                'time_info' => (string) ($status['time_info'] ?? 'Available all day'),
            ];
        })->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function fixedSchedulesByRoom(int $classroomId, CarbonInterface $rangeStart, CarbonInterface $rangeEnd): Collection
    {
        return $this->itScopedSchedules()
            ->with(['course.instructor'])
            ->where('classroom_id', $classroomId)
            ->where('start_at', '<', $rangeEnd)
            ->where('end_at', '>', $rangeStart)
            ->orderBy('start_at')
            ->get()
            ->map(function (Schedule $schedule): array {
                return [
                    'id' => $schedule->id,
                    'course' => (string) ($schedule->course?->title ?? 'Untitled Subject'),
                    'course_code' => (string) ($schedule->course?->code ?? ''),
                    'instructor' => (string) ($schedule->course?->instructor?->name ?? 'Unassigned Instructor'),
                    'start_at' => optional($schedule->start_at)->toIso8601String(),
                    'end_at' => optional($schedule->end_at)->toIso8601String(),
                    'status' => (string) ($schedule->status ?? 'scheduled'),
                ];
            })
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function roomCurrentStatus(int $classroomId, ?CarbonInterface $at = null): array
    {
        $at ??= now();

        $classroom = Classroom::query()->find($classroomId);
        $currentSchedule = $this->itScopedSchedules()
            ->with(['course.instructor'])
            ->where('classroom_id', $classroomId)
            ->where('start_at', '<=', $at)
            ->where('end_at', '>', $at)
            ->orderBy('start_at')
            ->first();

        $currentReservation = $this->itScopedReservations()
            ->with(['user'])
            ->where('classroom_id', $classroomId)
            ->where('start_at', '<=', $at)
            ->where('end_at', '>', $at)
            ->orderBy('start_at')
            ->first();

        $nextSchedule = $this->itScopedSchedules()
            ->with(['course.instructor'])
            ->where('classroom_id', $classroomId)
            ->where('start_at', '>', $at)
            ->orderBy('start_at')
            ->first();

        if ($currentSchedule) {
            return [
                'status' => 'occupied',
                'status_label' => 'Occupied',
                'source' => 'official_schedule',
                'current' => [
                    'type' => 'official_schedule',
                    'id' => $currentSchedule->id,
                    'course' => (string) ($currentSchedule->course?->title ?? 'Untitled Subject'),
                    'instructor' => (string) ($currentSchedule->course?->instructor?->name ?? 'Unassigned Instructor'),
                    'start_at' => optional($currentSchedule->start_at)->toIso8601String(),
                    'end_at' => optional($currentSchedule->end_at)->toIso8601String(),
                ],
                'next_schedule' => $this->formatNextSchedule($nextSchedule),
            ];
        }

        if ($currentReservation) {
            return [
                'status' => 'reserved',
                'status_label' => 'Reserved',
                'source' => 'reservation',
                'current' => [
                    'type' => 'reservation',
                    'id' => $currentReservation->id,
                    'reserved_by' => (string) ($currentReservation->user?->name ?? 'Faculty'),
                    'start_at' => optional($currentReservation->start_at)->toIso8601String(),
                    'end_at' => optional($currentReservation->end_at)->toIso8601String(),
                ],
                'next_schedule' => $this->formatNextSchedule($nextSchedule),
            ];
        }

        if ($classroom && (int) $classroom->current_occupancy > 0) {
            return [
                'status' => 'occupied',
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
            'status' => 'available',
            'status_label' => 'Available',
            'source' => null,
            'current' => null,
            'next_schedule' => $this->formatNextSchedule($nextSchedule),
        ];
    }

    private function itScopedSchedules(): Builder
    {
        return Schedule::query()
            ->whereIn('status', self::ACTIVE_SCHEDULE_STATUSES)
            ->whereHas('course.instructor', function ($query): void {
                $this->applyItDepartmentScope($query);
            });
    }

    private function itScopedReservations(): Builder
    {
        return Reservation::query()
           ->whereIn('status', self::ACTIVE_RESERVATION_STATUSES);
    }


    /**
     * @return array{lat: float, lng: float}
     */
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

        $fallback = [
            ['lat' => 16.0, 'lng' => 120.0],
            ['lat' => 16.0008, 'lng' => 120.0006],
            ['lat' => 16.0004, 'lng' => 120.0012],
            ['lat' => 15.9995, 'lng' => 120.0002],
        ];

        return $fallback[$index % count($fallback)];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatNextSchedule(?Schedule $schedule): ?array
    {
        if (! $schedule) {
            return null;
        }

        return [
            'id' => $schedule->id,
            'course' => (string) ($schedule->course?->title ?? 'Untitled Subject'),
            'instructor' => (string) ($schedule->course?->instructor?->name ?? 'Unassigned Instructor'),
            'start_at' => optional($schedule->start_at)->toIso8601String(),
            'end_at' => optional($schedule->end_at)->toIso8601String(),
            'status' => (string) ($schedule->status ?? 'scheduled'),
        ];
    }
}