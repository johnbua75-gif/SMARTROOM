<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function __construct(
        private RoomAvailabilityService $availabilityService
    ) {}

    public function applyItDepartmentScope($query): void
    {
        $query->where(function ($scope): void {
            $scope->whereIn(DB::raw('LOWER(TRIM(COALESCE(department, \'\')))'), ['it', 'cit', 'cite', 'ict', 'bsit'])
                ->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%information technology%'])
                ->orWhereRaw("LOWER(COALESCE(department, '')) LIKE ?", ['%computer science%']);
        });
    }

    public function ensureItScheduleScope(Schedule $schedule): void
    {
        $schedule->loadMissing(['course.instructor', 'courseOffering.instructor']);

        $instructor = $schedule->courseOffering?->instructor ?? $schedule->course?->instructor;

        if (! $this->availabilityService->isItUserDepartment($instructor?->department)) {
            abort(404);
        }
    }

    public function storeFacultySchedule(User $user, array $validated): void
    {
        $timeToMinutes = function (string $value): int {
            [$hour, $minute] = array_map('intval', explode(':', $value));

            return ($hour * 60) + $minute;
        };

        if (empty($validated['day1']) && empty($validated['day2'])) {
            throw ValidationException::withMessages([
                'day1' => ['Select at least one schedule day.'],
            ]);
        }

        if (! empty($validated['day1']) && $timeToMinutes($validated['day1_end']) <= $timeToMinutes($validated['day1_start'])) {
            throw ValidationException::withMessages([
                'day1_end' => ['Day 1 end time must be after the start time.'],
            ]);
        }

        if (! empty($validated['day2']) && $timeToMinutes($validated['day2_end']) <= $timeToMinutes($validated['day2_start'])) {
            throw ValidationException::withMessages([
                'day2_end' => ['Day 2 end time must be after the start time.'],
            ]);
        }

        $semesterStart = Carbon::parse($validated['semester_start'])->startOfDay();
        $semesterEnd = Carbon::parse($validated['semester_end'])->endOfDay();
        $offering = null;

        if (! empty($validated['course_offering_id'])) {
            $offering = CourseOffering::query()
                ->where('instructor_user_id', $user->id)
                ->with('course')
                ->find($validated['course_offering_id']);

            if (! $offering || ! $offering->course) {
                throw ValidationException::withMessages([
                    'course_offering_id' => ['You can only schedule offerings assigned to your account.'],
                ]);
            }

            $course = $offering->course;
            $blockSection = $offering->block_section;
            $classroomId = $offering->classroom_id;

            if (
                $semesterStart->toDateString() !== $offering->term_start->toDateString()
                || $semesterEnd->toDateString() !== $offering->term_end->toDateString()
            ) {
                throw ValidationException::withMessages([
                    'semester_start' => ['The selected dates must match the assigned offering term.'],
                ]);
            }
        } else {
            $course = Course::query()
                ->where('instructor_user_id', $user->id)
                ->find($validated['course_id']);

            if (! $course) {
                throw ValidationException::withMessages([
                    'course_id' => ['You can only add schedules for subjects assigned to your account.'],
                ]);
            }

            $blockSection = (string) $validated['block_section'];
            $classroomId = $course->classroom_id ?: $course->schedules()
                ->latest('start_at')
                ->value('classroom_id');
        }

        if (! $classroomId) {
            throw ValidationException::withMessages([
                $offering ? 'course_offering_id' : 'course_id' => ['An admin must assign a room to this offering before you can add its schedule.'],
            ]);
        }

        $buildOccurrences = function (int $isoDay, string $startTime, string $endTime) use ($semesterStart, $semesterEnd): array {
            $start = $semesterStart->copy();
            $delta = ($isoDay - $start->dayOfWeekIso + 7) % 7;
            $firstDate = $start->copy()->addDays($delta);

            $occurrences = [];
            $cursor = $firstDate->copy();

            while ($cursor->lte($semesterEnd)) {
                $startAt = Carbon::parse($cursor->toDateString().' '.$startTime);
                $endAt = Carbon::parse($cursor->toDateString().' '.$endTime);
                $occurrences[] = [
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'day_of_week' => (int) $startAt->dayOfWeek,
                ];
                $cursor->addWeek();
            }

            return $occurrences;
        };

        $occurrences = [];

        if (! empty($validated['day1'])) {
            $occurrences = $buildOccurrences(
                (int) $validated['day1'],
                $validated['day1_start'],
                $validated['day1_end']
            );
        }

        if (! empty($validated['day2'])) {
            $occurrences = array_merge(
                $occurrences,
                $buildOccurrences((int) $validated['day2'], $validated['day2_start'], $validated['day2_end'])
            );
        }

        if (empty($occurrences)) {
            throw ValidationException::withMessages([
                'semester_start' => ['No schedule dates were generated for the selected semester range.'],
            ]);
        }

        usort($occurrences, function (array $a, array $b): int {
            return $a['start_at'] <=> $b['start_at'];
        });

        DB::transaction(function () use ($occurrences, $validated, $classroomId, $user, $course, $offering, $blockSection, $semesterStart, $semesterEnd): void {
            $offering = $offering
                ? CourseOffering::query()->lockForUpdate()->findOrFail($offering->id)
                : $this->resolveCourseOffering(
                    (int) $course->id,
                    (int) $user->id,
                    (int) $classroomId,
                    $blockSection,
                    $semesterStart,
                    $semesterEnd
                );
            $seriesId = (string) Str::uuid();
            foreach ($occurrences as $occurrence) {
                if ($occurrence['start_at']->format('H:i') < '06:00' || $occurrence['end_at']->format('H:i') > '17:00') {
                    throw ValidationException::withMessages([
                        'day1_start' => [
                            'Schedules must be within room operating hours: 6:00 AM to 5:00 PM.',
                        ],
                    ]);
                }

                $conflict = $this->availabilityService->checkOfficialScheduleConflict(
                    (int) $classroomId,
                    $occurrence['start_at'],
                    $occurrence['end_at'],
                    null,
                    true
                );

                if ($conflict['has_conflict']) {
                    throw ValidationException::withMessages([
                        'classroom_id' => [
                            'Official schedule conflict on '.$occurrence['start_at']->format('M d, Y g:i A').'.',
                        ],
                    ]);
                }

                $this->ensureInstructorAvailable(
                    (int) $offering->instructor_user_id,
                    $occurrence['start_at'],
                    $occurrence['end_at'],
                    'day1_start'
                );
            }

            $now = now();
            $rows = [];

            foreach ($occurrences as $occurrence) {
                $rows[] = [
                    'classroom_id' => $classroomId,
                    'course_id' => $course->id,
                    'course_offering_id' => $offering->id,
                    'block_section' => $blockSection,
                    'series_id' => $seriesId,
                    'start_at' => $occurrence['start_at'],
                    'end_at' => $occurrence['end_at'],
                    'status' => 'scheduled',
                    'day_of_week' => $occurrence['day_of_week'],
                    'enrolled' => $validated['enrolled'] ?? 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Schedule::insert($rows);
        });
    }

    public function cancelFacultySchedule(User $user, Schedule $schedule, array $validated): void
    {
        $ownedSchedule = Schedule::query()
            ->whereKey($schedule->id)
            ->forInstructor((int) $user->id)
            ->firstOrFail();

        if ($ownedSchedule->status === 'cancelled') {
            throw ValidationException::withMessages([
                'schedule' => ['Class is already cancelled.'],
            ]);
        }

        if ($ownedSchedule->status === 'completed') {
            throw ValidationException::withMessages([
                'schedule' => ['Completed class can no longer be cancelled.'],
            ]);
        }

        $ownedSchedule->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);
    }

    public function storeSchedule(array $payload): array
    {
        unset($payload['year_level']);

        $hasSemesterPattern = isset($payload['semester_start'], $payload['semester_end'])
            && isset($payload['day1'], $payload['day1_start'], $payload['day1_end'], $payload['day2'], $payload['day2_start'], $payload['day2_end']);

        if (isset($payload['semester_start']) && ! $hasSemesterPattern) {
            DB::transaction(function () use ($payload): void {
                $this->resolveCourseOffering(
                    (int) $payload['course_id'],
                    isset($payload['instructor_user_id']) ? (int) $payload['instructor_user_id'] : null,
                    isset($payload['classroom_id']) ? (int) $payload['classroom_id'] : null,
                    (string) ($payload['block_section'] ?? 'Unspecified'),
                    Carbon::parse((string) $payload['semester_start'])->startOfDay(),
                    Carbon::parse((string) $payload['semester_end'])->startOfDay()
                );
            });

            return [
                'type' => 'partial',
                'message' => 'Subject and room assignment saved. Add day and time details later to create room schedules.',
            ];
        }

        if ($hasSemesterPattern) {
            $semesterStart = Carbon::parse((string) $payload['semester_start'])->startOfDay();
            $semesterEnd = Carbon::parse((string) $payload['semester_end'])->endOfDay();

            $buildOccurrences = function (int $isoDay, string $startTime, string $endTime) use ($semesterStart, $semesterEnd): array {
                $start = $semesterStart->copy();
                $delta = ($isoDay - $start->dayOfWeekIso + 7) % 7;
                $firstDate = $start->copy()->addDays($delta);

                $occurrences = [];
                $cursor = $firstDate->copy();

                while ($cursor->lte($semesterEnd)) {
                    $startAt = Carbon::parse($cursor->toDateString().' '.$startTime);
                    $endAt = Carbon::parse($cursor->toDateString().' '.$endTime);
                    $occurrences[] = [
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                        'day_of_week' => (int) $startAt->dayOfWeek,
                    ];
                    $cursor->addWeek();
                }

                return $occurrences;
            };

            $occurrences = array_merge(
                $buildOccurrences((int) $payload['day1'], (string) $payload['day1_start'], (string) $payload['day1_end']),
                $buildOccurrences((int) $payload['day2'], (string) $payload['day2_start'], (string) $payload['day2_end'])
            );

            if (empty($occurrences)) {
                throw ValidationException::withMessages([
                    'semester_start' => ['No schedule dates were generated for the selected semester range.'],
                ]);
            }

            usort($occurrences, function (array $a, array $b): int {
                return $a['start_at'] <=> $b['start_at'];
            });

            DB::transaction(function () use ($occurrences, $payload, $semesterStart, $semesterEnd): void {
                $offering = $this->resolveCourseOffering(
                    (int) $payload['course_id'],
                    isset($payload['instructor_user_id']) ? (int) $payload['instructor_user_id'] : null,
                    (int) $payload['classroom_id'],
                    (string) ($payload['block_section'] ?? 'Unspecified'),
                    $semesterStart,
                    $semesterEnd
                );
                $seriesId = (string) Str::uuid();
                foreach ($occurrences as $occurrence) {
                    $scheduleConflict = $this->availabilityService->checkOfficialScheduleConflict(
                        (int) $payload['classroom_id'],
                        $occurrence['start_at'],
                        $occurrence['end_at'],
                        null,
                        true
                    );

                    if ($scheduleConflict['has_conflict']) {
                        throw ValidationException::withMessages([
                            'classroom_id' => ['Official schedule conflict on '.$occurrence['start_at']->format('M d, Y g:i A').'. Room is already occupied.'],
                        ]);
                    }

                    $reservationConflict = $this->availabilityService->checkReservationConflict(
                        (int) $payload['classroom_id'],
                        $occurrence['start_at'],
                        $occurrence['end_at'],
                        null,
                        true
                    );

                    if ($reservationConflict['has_conflict']) {
                        throw ValidationException::withMessages([
                            'classroom_id' => ['Reservation conflict on '.$occurrence['start_at']->format('M d, Y g:i A').'. Room is already reserved.'],
                        ]);
                    }

                    if (in_array((string) ($payload['status'] ?? 'scheduled'), ['scheduled', 'ongoing'], true)) {
                        $this->ensureInstructorAvailable(
                            $offering->instructor_user_id,
                            $occurrence['start_at'],
                            $occurrence['end_at'],
                            'instructor_user_id'
                        );
                    }
                }

                $now = now();
                $rows = [];

                foreach ($occurrences as $occurrence) {
                    $rows[] = [
                        'classroom_id' => $payload['classroom_id'],
                        'course_id' => $payload['course_id'],
                        'course_offering_id' => $offering->id,
                        'block_section' => $payload['block_section'] ?? null,
                        'series_id' => $seriesId,
                        'start_at' => $occurrence['start_at'],
                        'end_at' => $occurrence['end_at'],
                        'status' => $payload['status'] ?? 'scheduled',
                        'day_of_week' => $occurrence['day_of_week'],
                        'enrolled' => $payload['enrolled'] ?? 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                Schedule::insert($rows);
            });

            return [
                'type' => 'recurring',
                'created_count' => count($occurrences),
            ];
        }

        $startAt = Carbon::parse((string) $payload['start_at']);
        $endAt = Carbon::parse((string) $payload['end_at']);
        $repeatUntil = isset($payload['repeat_until'])
            ? Carbon::parse((string) $payload['repeat_until'])->endOfDay()
            : null;

        $seriesId = (string) Str::uuid();
        $basePayload = $payload;
        unset($basePayload['repeat_until']);
        $basePayload['series_id'] = $seriesId;

        $termEnd = $repeatUntil?->copy()->startOfDay() ?? $startAt->copy()->startOfDay();

        $createdSchedules = DB::transaction(function () use ($basePayload, $startAt, $endAt, $repeatUntil, $termEnd) {
            $offering = $this->resolveCourseOffering(
                (int) $basePayload['course_id'],
                isset($basePayload['instructor_user_id']) ? (int) $basePayload['instructor_user_id'] : null,
                (int) $basePayload['classroom_id'],
                (string) ($basePayload['block_section'] ?? 'Unspecified'),
                $startAt->copy()->startOfDay(),
                $termEnd
            );
            $created = collect();

            $occurrenceStart = $startAt->copy();
            $occurrenceEnd = $endAt->copy();

            while ($repeatUntil === null || $occurrenceStart->lte($repeatUntil)) {
                $conflict = $this->availabilityService->checkOfficialScheduleConflict(
                    (int) $basePayload['classroom_id'],
                    $occurrenceStart,
                    $occurrenceEnd,
                    null,
                    true
                );

                if ($conflict['has_conflict']) {
                    throw ValidationException::withMessages([
                        'classroom_id' => ['Official schedule conflict on '.$occurrenceStart->format('M d, Y h:i A').'. Room is already occupied.'],
                    ]);
                }

                $reservationConflict = $this->availabilityService->checkReservationConflict(
                    (int) $basePayload['classroom_id'],
                    $occurrenceStart,
                    $occurrenceEnd,
                    null,
                    true
                );

                if ($reservationConflict['has_conflict']) {
                    throw ValidationException::withMessages([
                        'classroom_id' => ['Reservation conflict on '.$occurrenceStart->format('M d, Y h:i A').'. Room is already reserved.'],
                    ]);
                }

                if (in_array((string) ($basePayload['status'] ?? 'scheduled'), ['scheduled', 'ongoing'], true)) {
                    $this->ensureInstructorAvailable(
                        $offering->instructor_user_id,
                        $occurrenceStart,
                        $occurrenceEnd,
                        'instructor_user_id'
                    );
                }

                $occurrencePayload = $basePayload;
                $occurrencePayload['course_offering_id'] = $offering->id;
                $occurrencePayload['start_at'] = $occurrenceStart->copy();
                $occurrencePayload['end_at'] = $occurrenceEnd->copy();
                $occurrencePayload['day_of_week'] = $occurrenceStart->dayOfWeek;

                $created->push(Schedule::create($occurrencePayload));

                if ($repeatUntil === null) {
                    break;
                }

                $occurrenceStart->addWeek();
                $occurrenceEnd->addWeek();
            }

            return $created;
        });

        return [
            'type' => 'single',
            'created_count' => $createdSchedules->count(),
            'schedule' => $createdSchedules->first(),
        ];
    }

    public function resolveCourseOffering(
        int $courseId,
        ?int $instructorUserId,
        ?int $classroomId,
        string $blockSection,
        Carbon $termStart,
        Carbon $termEnd
    ): CourseOffering {
        $course = Course::query()
            ->lockForUpdate()
            ->findOrFail($courseId);

        $instructorUserId ??= $course->instructor_user_id ? (int) $course->instructor_user_id : null;
        $blockSection = trim($blockSection) !== '' ? trim($blockSection) : 'Unspecified';
        $termStart = $termStart->copy()->startOfDay();
        $termEnd = $termEnd->copy()->startOfDay();

        if ($termEnd->lt($termStart)) {
            throw ValidationException::withMessages([
                'semester_end' => ['The offering end date must be on or after its start date.'],
            ]);
        }

        $offering = CourseOffering::query()
            ->where('course_id', $course->id)
            ->where('block_section', $blockSection)
            ->whereDate('term_start', $termStart->toDateString())
            ->whereDate('term_end', $termEnd->toDateString())
            ->lockForUpdate()
            ->first();

        if ($offering) {
            if (
                $offering->instructor_user_id !== null
                && $instructorUserId !== null
                && (int) $offering->instructor_user_id !== $instructorUserId
            ) {
                if ($offering->schedules()->exists() || $offering->enrollments()->exists()) {
                    throw ValidationException::withMessages([
                        'instructor_user_id' => ['This subject section cannot be reassigned after schedules or enrollments exist. Create a new offering instead.'],
                    ]);
                }

                $offering->instructor_user_id = $instructorUserId;
            }

            if (
                $offering->classroom_id !== null
                && $classroomId !== null
                && (int) $offering->classroom_id !== $classroomId
            ) {
                throw ValidationException::withMessages([
                    'classroom_id' => ['This subject section already has a different room assigned for the selected term.'],
                ]);
            }

            if ($offering->instructor_user_id === null && $instructorUserId !== null) {
                $offering->instructor_user_id = $instructorUserId;
            }

            if ($offering->classroom_id === null && $classroomId !== null) {
                $offering->classroom_id = $classroomId;
            }

            if ($offering->isDirty()) {
                $offering->save();
            }

            return $offering;
        }

        return CourseOffering::query()->create([
            'course_id' => $course->id,
            'instructor_user_id' => $instructorUserId,
            'classroom_id' => $classroomId,
            'block_section' => $blockSection,
            'term_start' => $termStart,
            'term_end' => $termEnd,
        ]);
    }

    private function ensureInstructorAvailable(
        ?int $instructorUserId,
        Carbon $startAt,
        Carbon $endAt,
        string $errorKey
    ): void {
        if (! $instructorUserId || ! $this->availabilityService->hasInstructorScheduleConflict(
            $instructorUserId,
            $startAt,
            $endAt,
            null,
            true
        )) {
            return;
        }

        throw ValidationException::withMessages([
            $errorKey => [
                'Selected faculty member already has another class scheduled during '.$startAt->format('M d, Y g:i A').'.',
            ],
        ]);
    }

    public function updateSchedule(Schedule $schedule, array $payload, bool $applyToSeries): array
    {
        $this->ensureItScheduleScope($schedule);
        $seriesId = $schedule->series_id;

        if ($applyToSeries && $seriesId) {
            $updatePayload = array_intersect_key($payload, array_flip([
                'classroom_id',
                'course_id',
                'status',
                'enrolled',
                'block_section',
            ]));

            DB::transaction(function () use ($seriesId, $updatePayload): void {
                $seriesSchedules = Schedule::query()->where('series_id', $seriesId)->get();

                $firstSchedule = $seriesSchedules->first();
                $targetCourseId = (int) ($updatePayload['course_id'] ?? $firstSchedule->course_id);
                $targetBlockSection = (string) ($updatePayload['block_section'] ?? $firstSchedule->block_section ?? 'Unspecified');
                $currentOffering = $firstSchedule->courseOffering;

                if (
                    $targetCourseId !== (int) $firstSchedule->course_id
                    || $targetBlockSection !== (string) ($currentOffering?->block_section ?? $firstSchedule->block_section ?? 'Unspecified')
                ) {
                    $startDates = $seriesSchedules->pluck('start_at')->filter();
                    $termStart = $currentOffering?->term_start
                        ? Carbon::parse($currentOffering->term_start)
                        : Carbon::parse($startDates->min());
                    $termEnd = $currentOffering?->term_end
                        ? Carbon::parse($currentOffering->term_end)
                        : Carbon::parse($startDates->max());
                    $targetCourse = Course::query()->findOrFail($targetCourseId);
                    $offering = $this->resolveCourseOffering(
                        $targetCourseId,
                        $targetCourse->instructor_user_id ? (int) $targetCourse->instructor_user_id : null,
                        (int) ($updatePayload['classroom_id'] ?? $firstSchedule->classroom_id),
                        $targetBlockSection,
                        $termStart,
                        $termEnd
                    );
                    $updatePayload['course_offering_id'] = $offering->id;
                }

                foreach ($seriesSchedules as $seriesSchedule) {
                    $targetClassroomId = (int) ($updatePayload['classroom_id'] ?? $seriesSchedule->classroom_id);
                    $startAt = $seriesSchedule->start_at;
                    $endAt = $seriesSchedule->end_at;

                    if ($startAt && $endAt) {
                        $conflict = $this->availabilityService->checkOfficialScheduleConflict(
                            $targetClassroomId,
                            $startAt,
                            $endAt,
                            (int) $seriesSchedule->id,
                            true
                        );

                        if ($conflict['has_conflict']) {
                            throw ValidationException::withMessages([
                                'classroom_id' => ['Official schedule conflict on '.$startAt->format('M d, Y h:i A').'. Room is already occupied.'],
                            ]);
                        }

                        $reservationConflict = $this->availabilityService->checkReservationConflict(
                            $targetClassroomId,
                            $startAt,
                            $endAt,
                            null,
                            true
                        );

                        if ($reservationConflict['has_conflict']) {
                            throw ValidationException::withMessages([
                                'classroom_id' => ['Reservation conflict on '.$startAt->format('M d, Y h:i A').'. Room is already reserved.'],
                            ]);
                        }
                    }
                }

                foreach ($seriesSchedules as $seriesSchedule) {
                    $seriesSchedule->update($updatePayload);
                }
            });

            return [
                'type' => 'series',
                'series_id' => $seriesId,
            ];
        }

        unset($payload['repeat_until']);

        $effectiveStartAt = isset($payload['start_at']) ? Carbon::parse($payload['start_at']) : $schedule->start_at;
        $effectiveEndAt = isset($payload['end_at']) ? Carbon::parse($payload['end_at']) : $schedule->end_at;

        if ($effectiveStartAt) {
            $payload['day_of_week'] = $effectiveStartAt->dayOfWeek;
        }

        $createdCount = DB::transaction(function () use ($schedule, $payload, $effectiveStartAt, $effectiveEndAt, $repeatUntil): int {
            $targetClassroomId = (int) ($payload['classroom_id'] ?? $schedule->classroom_id);

            $targetCourseId = (int) ($payload['course_id'] ?? $schedule->course_id);
            $targetBlockSection = (string) ($payload['block_section'] ?? $schedule->block_section ?? 'Unspecified');
            $schedule->loadMissing('courseOffering');
            $currentOffering = $schedule->courseOffering;

            if (
                $targetCourseId !== (int) $schedule->course_id
                || $targetBlockSection !== (string) ($currentOffering?->block_section ?? $schedule->block_section ?? 'Unspecified')
            ) {
                $targetCourse = Course::query()->findOrFail($targetCourseId);
                $termStart = $currentOffering?->term_start
                    ? Carbon::parse($currentOffering->term_start)
                    : $effectiveStartAt->copy()->startOfDay();
                $termEnd = $currentOffering?->term_end
                    ? Carbon::parse($currentOffering->term_end)
                    : $effectiveEndAt->copy()->startOfDay();
                $offering = $this->resolveCourseOffering(
                    $targetCourseId,
                    $targetCourse->instructor_user_id ? (int) $targetCourse->instructor_user_id : null,
                    $targetClassroomId,
                    $targetBlockSection,
                    $termStart,
                    $termEnd
                );
                $payload['course_offering_id'] = $offering->id;
            }

            $conflict = $this->availabilityService->checkOfficialScheduleConflict(
                $targetClassroomId,
                $effectiveStartAt,
                $effectiveEndAt,
                (int) $schedule->id,
                true
            );

            if ($conflict['has_conflict']) {
                throw ValidationException::withMessages([
                    'classroom_id' => ['Official schedule conflict: room is already occupied by another official schedule at selected time.'],
                ]);
            }

            $reservationConflict = $this->availabilityService->checkReservationConflict(
                $targetClassroomId,
                $effectiveStartAt,
                $effectiveEndAt,
                (int) $schedule->id,
                true
            );

            if ($reservationConflict['has_conflict']) {
                throw ValidationException::withMessages([
                    'classroom_id' => ['Reservation conflict: room is already reserved at selected time.'],
                ]);
            }

            $schedule->update($payload);
            $created = 0;

            if ($repeatUntil && $effectiveStartAt && $effectiveEndAt) {
                $occurrenceStart = $effectiveStartAt->copy()->addWeek();
                $occurrenceEnd = $effectiveEndAt->copy()->addWeek();

                while ($occurrenceStart->lte($repeatUntil)) {
                    $loopConflict = $this->availabilityService->checkOfficialScheduleConflict(
                        $targetClassroomId,
                        $occurrenceStart,
                        $occurrenceEnd,
                        null,
                        true
                    );

                    if ($loopConflict['has_conflict']) {
                        throw ValidationException::withMessages([
                            'classroom_id' => ['Official schedule conflict on '.$occurrenceStart->format('M d, Y h:i A').'. Room is already occupied.'],
                        ]);
                    }

                    $loopReservationConflict = $this->availabilityService->checkReservationConflict(
                        $targetClassroomId,
                        $occurrenceStart,
                        $occurrenceEnd,
                        null,
                        true
                    );

                    if ($loopReservationConflict['has_conflict']) {
                        throw ValidationException::withMessages([
                            'classroom_id' => ['Reservation conflict on '.$occurrenceStart->format('M d, Y h:i A').'. Room is already reserved.'],
                        ]);
                    }

                    $copyPayload = array_merge($schedule->only([
                        'classroom_id',
                        'course_id',
                        'course_offering_id',
                        'status',
                        'enrolled',
                        'block_section',
                        'series_id',
                    ]), [
                        'start_at' => $occurrenceStart->copy(),
                        'end_at' => $occurrenceEnd->copy(),
                        'day_of_week' => $occurrenceStart->dayOfWeek,
                    ]);

                    Schedule::create($copyPayload);
                    $created++;

                    $occurrenceStart->addWeek();
                    $occurrenceEnd->addWeek();
                }
            }

            return $created;
        });

        return [
            'type' => 'single',
            'created_count' => $createdCount,
        ];
    }

    public function destroySchedule(Schedule $schedule): void
    {
        $this->ensureItScheduleScope($schedule);
        $schedule->delete();
    }

    public function bulkDestroySchedules(array $requestedIds): int
    {
        $requestedIds = collect($requestedIds)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $schedules = Schedule::query()
            ->with(['course.instructor', 'courseOffering.instructor'])
            ->whereIn('id', $requestedIds)
            ->get();

        $allowedIds = $schedules
            ->filter(function (Schedule $schedule): bool {
                $instructor = $schedule->courseOffering?->instructor ?? $schedule->course?->instructor;

                return $this->availabilityService->isItUserDepartment($instructor?->department);
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($allowedIds->count() !== $requestedIds->count()) {
            throw ValidationException::withMessages([
                'schedule_ids' => ['Some selected schedules are outside your allowed department scope.'],
            ]);
        }

        return Schedule::query()->whereIn('id', $allowedIds)->delete();
    }
}
