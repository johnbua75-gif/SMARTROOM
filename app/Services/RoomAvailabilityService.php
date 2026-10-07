<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\User;
use App\Support\DepartmentScope;
use App\Support\RoomAvailabilityStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        if ($classroom && in_array((string) $classroom->status, RoomAvailabilityStatus::BLOCKED_CLASSROOM_STATUSES, true)) {
            return [
                'available' => false,
                'status' => RoomAvailabilityStatus::MAINTENANCE,
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
                'status' => RoomAvailabilityStatus::OCCUPIED,
                'reason' => 'Room is occupied by official schedule at selected time.',
                'conflicts' => $conflicts,
            ];
        }

        if ($reservationConflicts->isNotEmpty()) {
            return [
                'available' => false,
                'status' => RoomAvailabilityStatus::RESERVED,
                'reason' => 'Room is already reserved at selected time.',
                'conflicts' => $conflicts,
            ];
        }

        if ($classroom && (int) $classroom->current_occupancy > 0 && $startAt->lte(now()) && $endAt->gt(now())) {
            return [
                'available' => false,
                'status' => RoomAvailabilityStatus::OCCUPIED,
                'reason' => 'Room is currently occupied.',
                'conflicts' => [],
            ];
        }

        return [
            'available' => true,
            'status' => RoomAvailabilityStatus::AVAILABLE,
            'reason' => null,
            'conflicts' => [],
        ];
    }

    public function lockClassroomForUpdate(int $classroomId): Classroom
    {
        $classroom = Classroom::query()
            ->whereKey($classroomId)
            ->lockForUpdate()
            ->first();

        if (! $classroom) {
            throw (new ModelNotFoundException)->setModel(Classroom::class, [$classroomId]);
        }

        return $classroom;
    }

    public function hasInstructorScheduleConflict(
        int $instructorUserId,
        CarbonInterface $startAt,
        CarbonInterface $endAt,
        ?int $ignoreScheduleId = null,
        bool $forUpdateLock = false
    ): bool {
        if ($forUpdateLock) {
            User::query()->whereKey($instructorUserId)->lockForUpdate()->first();
        }

        $query = Schedule::query()
            ->whereIn('status', self::ACTIVE_SCHEDULE_STATUSES)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->whereHas('course', function (Builder $courseQuery) use ($instructorUserId): void {
                $courseQuery->where('instructor_user_id', $instructorUserId);
            });

        if ($ignoreScheduleId !== null) {
            $query->where('id', '!=', $ignoreScheduleId);
        }

        return $query->exists();
    }

    /**
     * @return array{available: false, status: string, reason: string, conflicts: array<int, array<string, mixed>>}|null
     */
    public function classroomUnavailability(Classroom $classroom): ?array
    {
        if (! in_array((string) $classroom->status, RoomAvailabilityStatus::BLOCKED_CLASSROOM_STATUSES, true)) {
            return null;
        }

        return [
            'available' => false,
            'status' => RoomAvailabilityStatus::MAINTENANCE,
            'reason' => $classroom->unavailable_reason ?: 'Room is temporarily unavailable due to an issue.',
            'conflicts' => [],
        ];
    }

    /**
     * @param  Collection<int, Classroom>  $classrooms
     * @return Collection<int, array<string, mixed>>
     */
    public function buildRoomStatuses(Collection $classrooms, CarbonInterface $startAt, CarbonInterface $endAt, ?CarbonInterface $now = null): Collection
    {
        $now ??= now();

        $classroomIds = $classrooms->pluck('id')->filter()->values();
        if ($classroomIds->isEmpty()) {
            return collect();
        }

        $schedules = $this->itScopedSchedules()
            ->whereIn('classroom_id', $classroomIds)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->orderBy('start_at')
            ->get()
            ->groupBy('classroom_id');

        $reservations = $this->itScopedReservations()
            ->whereIn('classroom_id', $classroomIds)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->orderBy('start_at')
            ->get()
            ->groupBy('classroom_id');

        return $classrooms->map(function (Classroom $classroom) use ($schedules, $reservations, $now): array {
            if (in_array((string) $classroom->status, RoomAvailabilityStatus::BLOCKED_CLASSROOM_STATUSES, true)) {
                return [
                    'classroom_id' => $classroom->id,
                    'status' => RoomAvailabilityStatus::MAINTENANCE,
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

            $status = RoomAvailabilityStatus::AVAILABLE;
            $statusLabel = 'Available';
            $reason = null;
            $timeInfo = 'Available all day';

            if ($currentSchedule) {
                $status = RoomAvailabilityStatus::OCCUPIED;
                $statusLabel = 'Occupied';
                $reason = 'Room is occupied by official schedule at selected time.';

                if ($currentSchedule->end_at) {
                    $timeInfo = 'Free at '.$currentSchedule->end_at->format('g:i A');
                }
            } elseif ($currentReservation) {
                $status = RoomAvailabilityStatus::RESERVED;
                $statusLabel = 'Reserved';
                $reason = 'Room is already reserved at selected time.';

                if ($currentReservation->end_at) {
                    $timeInfo = 'Free at '.$currentReservation->end_at->format('g:i A');
                }
            } elseif ((int) $classroom->current_occupancy > 0) {
                $status = RoomAvailabilityStatus::OCCUPIED;
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
        $classroom = Classroom::query()->find($classroomId);
        if ($classroom && $this->classroomUnavailability($classroom)) {
            return [
                'has_conflict' => true,
                'message' => 'Room is temporarily unavailable due to an issue.',
                'conflicts' => [],
            ];
        }

        if ($forUpdateLock) {
            $this->lockClassroomForUpdate($classroomId);
        }

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
        if ($forUpdateLock) {
            $this->lockClassroomForUpdate($classroomId);
        }

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
            $scope->whereRaw(
                "LOWER(TRIM(COALESCE(department, ''))) IN (?, ?, ?, ?, ?)",
                ['it', 'cit', 'cite', 'ict', 'bsit']
            )->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%information technology%'])
                ->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%computer science%']);
        });
    }

    public function isItUserDepartment(?string $department): bool
    {
        return DepartmentScope::isItDepartment($department);
    }

    public function itScopedSchedules(): Builder
    {
        return Schedule::query()
            ->whereIn('status', self::ACTIVE_SCHEDULE_STATUSES)
            ->whereHas('course.instructor', function (Builder $query): void {
                $this->applyItDepartmentScope($query);
            });
    }

    public function itScopedCancelledSchedules(): Builder
    {
        return Schedule::query()
            ->where('status', 'cancelled')
            ->whereHas('course.instructor', function (Builder $query): void {
                $this->applyItDepartmentScope($query);
            });
    }

    public function itScopedReservations(): Builder
    {
        return Reservation::query()
            ->whereIn('status', self::ACTIVE_RESERVATION_STATUSES)
            ->whereHas('user', function (Builder $query): void {
                $this->applyItDepartmentScope($query);
            });
    }

    public function itScopedClassrooms(): Builder
    {
        return Classroom::query()
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('schedules.course.instructor', function (Builder $instructorQuery): void {
                        $this->applyItDepartmentScope($instructorQuery);
                    })
                    ->orWhereHas('reservations.user', function (Builder $userQuery): void {
                        $this->applyItDepartmentScope($userQuery);
                    });
            });
    }
}
