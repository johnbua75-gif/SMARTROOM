<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StoreScheduleRequest;
use App\Http\Requests\Api\UpdateScheduleRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use App\Services\RoomAvailabilityService;
use App\Services\ScheduleImportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function facultyIndex(Request $request): View
    {
        $user = $request->user();

        $facultyCourses = Course::query()
            ->where('instructor_user_id', $user->id)
            ->orderBy('code')
            ->get();

        $classrooms = Classroom::query()
            ->orderBy('building')
            ->orderBy('name')
            ->get();

        $facultySchedules = Schedule::query()
            ->with(['classroom', 'course'])
            ->whereHas('course', function ($query) use ($user): void {
                $query->where('instructor_user_id', $user->id);
            })
            ->orderBy('start_at')
            ->get();

        return view('frontend.faculty.schedule', [
            'facultyCourses' => $facultyCourses,
            'classrooms' => $classrooms,
            'facultySchedules' => $facultySchedules,
            'faculty_name' => $user->name,
            'faculty_dept' => $user->department ?? 'Faculty',
        ]);
    }

    public function facultyStore(Request $request, RoomAvailabilityService $availabilityService): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'course_id' => ['required', 'integer'],
            'block_section' => ['required', 'string', 'in:Block A,Block B'],
            'semester_start' => ['required', 'date'],
            'semester_end' => ['required', 'date', 'after_or_equal:semester_start'],
            'day1' => ['nullable', 'integer', 'between:1,5', 'different:day2'],
            'day1_start' => ['required_with:day1', 'date_format:H:i'],
            'day1_end' => ['required_with:day1', 'date_format:H:i'],
            'day2' => ['nullable', 'integer', 'between:1,5', 'different:day1'],
            'day2_start' => ['required_with:day2', 'date_format:H:i'],
            'day2_end' => ['required_with:day2', 'date_format:H:i'],
            'enrolled' => ['nullable', 'integer', 'min:0'],
        ]);

        $timeToMinutes = function (string $value): int {
            [$hour, $minute] = array_map('intval', explode(':', $value));

            return ($hour * 60) + $minute;
        };

        if (empty($validated['day1']) && empty($validated['day2'])) {
            return back()
                ->withInput()
                ->withErrors(['day1' => 'Select at least one schedule day.']);
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

        $course = Course::query()
            ->where('instructor_user_id', $user->id)
            ->whereHas('instructor', function ($query): void {
                $this->applyItDepartmentScope($query);
            })
            ->find($validated['course_id']);

        if (! $course) {
            return back()
                ->withInput()
                ->withErrors(['course_id' => 'You can only add schedules for your own subjects.']);
        }

        $classroomId = $course->classroom_id ?: $course->schedules()
            ->latest('start_at')
            ->value('classroom_id');

        if (! $classroomId) {
            return back()
                ->withInput()
                ->withErrors(['course_id' => 'An admin must assign a room to this subject before you can add its schedule.']);
        }

        $semesterStart = Carbon::parse($validated['semester_start'])->startOfDay();
        $semesterEnd = Carbon::parse($validated['semester_end'])->endOfDay();

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
            return back()
                ->withInput()
                ->withErrors(['semester_start' => 'No schedule dates were generated for the selected semester range.']);
        }

        usort($occurrences, function (array $a, array $b): int {
            return $a['start_at'] <=> $b['start_at'];
        });

        DB::transaction(function () use ($occurrences, $validated, $classroomId, $availabilityService, $user): void {
            $seriesId = (string) Str::uuid();
            foreach ($occurrences as $occurrence) {
                if ($occurrence['start_at']->format('H:i') < '06:00' || $occurrence['end_at']->format('H:i') > '17:00') {
                    throw ValidationException::withMessages([
                        'day1_start' => [
                            'Schedules must be within room operating hours: 6:00 AM to 5:00 PM.',
                        ],
                    ]);
                }

                $conflict = $availabilityService->checkOfficialScheduleConflict(
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

                $instructorConflict = Schedule::query()
                    ->whereIn('status', ['scheduled', 'ongoing'])
                    ->where('start_at', '<', $occurrence['end_at'])
                    ->where('end_at', '>', $occurrence['start_at'])
                    ->whereHas('course', function ($query) use ($user): void {
                        $query->where('instructor_user_id', $user->id);
                    })
                    ->exists();

                if ($instructorConflict) {
                    throw ValidationException::withMessages([
                        'day1_start' => [
                            'You already have another class scheduled during '.$occurrence['start_at']->format('M d, Y g:i A').'.',
                        ],
                    ]);
                }
            }

            $now = now();
            $rows = [];

            foreach ($occurrences as $occurrence) {
                $rows[] = [
                    'classroom_id' => $classroomId,
                    'course_id' => $validated['course_id'],
                    'block_section' => $validated['block_section'],
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

        return redirect()->route('faculty.schedule')->with('status', 'Schedule added for the whole semester.');
    }

    public function facultyCancel(Request $request, Schedule $schedule): RedirectResponse
    {
        $user = $request->user();

        $ownedSchedule = Schedule::query()
            ->whereKey($schedule->id)
            ->whereHas('course', function ($query) use ($user): void {
                $query->where('instructor_user_id', $user->id);
            })
            ->whereHas('course.instructor', function ($query): void {
                $this->applyItDepartmentScope($query);
            })
            ->firstOrFail();

        if ($ownedSchedule->status === 'cancelled') {
            return redirect()->route('faculty.schedule')->with('status', 'Class is already cancelled.');
        }

        if ($ownedSchedule->status === 'completed') {
            return redirect()->route('faculty.schedule')->withErrors([
                'schedule' => 'Completed class can no longer be cancelled.',
            ]);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        $ownedSchedule->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return redirect()->route('faculty.schedule')->with('status', 'Class cancelled successfully. The room is available for other teachers during this time.');
    }

    public function exportFacultyIcs(Request $request): StreamedResponse
    {
        $user = $request->user();

        $schedules = Schedule::query()
            ->with(['classroom', 'course'])
            ->whereHas('course', function ($query) use ($user): void {
                $query->where('instructor_user_id', $user->id);
            })
            ->whereHas('course.instructor', function ($query): void {
                $this->applyItDepartmentScope($query);
            })
            ->orderBy('start_at')
            ->get();

        $filename = 'faculty-schedule-'.now()->format('Ymd-His').'.ics';

        return response()->streamDownload(function () use ($schedules, $user): void {
            $lines = [
                'BEGIN:VCALENDAR',
                'VERSION:2.0',
                'PRODID:-//SmartRoom//Schedule//EN',
                'CALSCALE:GREGORIAN',
                'METHOD:PUBLISH',
            ];

            $timestamp = now()->utc()->format('Ymd\THis\Z');

            foreach ($schedules as $schedule) {
                if (! $schedule->start_at || ! $schedule->end_at) {
                    continue;
                }

                $startUtc = $schedule->start_at->copy()->utc()->format('Ymd\THis\Z');
                $endUtc = $schedule->end_at->copy()->utc()->format('Ymd\THis\Z');
                $courseCode = (string) ($schedule->course?->code ?? 'COURSE');
                $courseTitle = (string) ($schedule->course?->title ?? 'Class');
                $room = (string) ($schedule->classroom?->name ?? 'Room');
                $building = (string) ($schedule->classroom?->building ?? '');
                $location = trim($room.($building !== '' ? ', '.$building : ''));
                $summary = $courseCode.' - '.$courseTitle;
                $uid = 'schedule-'.$schedule->id.'@smartroom';

                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID:'.$uid;
                $lines[] = 'DTSTAMP:'.$timestamp;
                $lines[] = 'DTSTART:'.$startUtc;
                $lines[] = 'DTEND:'.$endUtc;
                $lines[] = 'SUMMARY:'.addcslashes($summary, ',;\\');
                $lines[] = 'LOCATION:'.addcslashes($location, ',;\\');
                $lines[] = 'DESCRIPTION:Instructor - '.addcslashes((string) $user->name, ',;\\');
                $lines[] = 'END:VEVENT';
            }

            $lines[] = 'END:VCALENDAR';

            echo implode("\r\n", $lines);
        }, $filename, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
        ]);
    }

    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $view = $request->query('view', 'week');

        $query = Schedule::query()
            ->with(['classroom', 'course.instructor']);

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $schedules = $query->orderBy('start_at')->get();
        $classrooms = Classroom::query()->orderBy('building')->orderBy('name')->get();
        // Include all courses for admin selection (allow first-year / college subjects like CC101)
        $courses = Course::query()
            ->with('instructor')
            ->orderBy('code')
            ->get();

        $facultyUsers = User::query()
            ->whereRaw("LOWER(COALESCE(role, '')) = ?", ['faculty'])
            ->with('courses.schedules.classroom')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department']);

        return view('frontend.admin.schedules', [
            'schedules' => $schedules,
            'classrooms' => $classrooms,
            'courses' => $courses,
            'facultyUsers' => $facultyUsers,
            'filter' => $filter,
            'view' => $view,
        ]);
    }

    public function subjectsByYearLevel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year_level' => ['required', 'integer', 'between:1,4'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $query = Course::query()
            ->with('instructor')
            ->whereHas('instructor', function ($scope): void {
                $this->applyItDepartmentScope($scope);
            })
            ->orderBy('code');

        if (! empty($validated['instructor_id'])) {
            $query->where('instructor_user_id', $validated['instructor_id']);
        }

        $courses = $query->get()->filter(function (Course $course) use ($validated): bool {
            return $course->yearLevel() === (int) $validated['year_level'];
        })->map(function (Course $course) {
            return [
                'id' => $course->id,
                'code' => $course->code,
                'title' => $course->title,
                'instructor_user_id' => $course->instructor_user_id,
            ];
        })->values();

        return response()->json(['courses' => $courses]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filter = (string) $request->query('filter', 'all');

        $query = Schedule::query()
            ->with(['classroom', 'course.instructor'])
            ->whereHas('course.instructor', function ($scope): void {
                $this->applyItDepartmentScope($scope);
            })
            ->orderBy('start_at');

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $filename = 'smartroom-schedules-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Schedule ID',
                'Course Code',
                'Course Title',
                'Block Section',
                'Instructor',
                'Classroom',
                'Building',
                'Start At',
                'End At',
                'Status',
                'Enrolled',
            ]);

            $query->chunk(500, function ($schedules) use ($handle): void {
                foreach ($schedules as $schedule) {
                    fputcsv($handle, [
                        $schedule->id,
                        $schedule->course?->code ?? '',
                        $schedule->course?->title ?? '',
                            $schedule->block_section ?? '',
                        $schedule->course?->instructor?->name ?? '',
                        $schedule->classroom?->name ?? '',
                        $schedule->classroom?->building ?? '',
                        $schedule->start_at?->format('Y-m-d H:i:s') ?? '',
                        $schedule->end_at?->format('Y-m-d H:i:s') ?? '',
                        $schedule->status ?? '',
                        (int) ($schedule->enrolled ?? 0),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importPreview(Request $request, ScheduleImportService $importService, RoomAvailabilityService $availabilityService): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xls,xlsx'],
            'default_classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        ]);

        try {
            $rows = $importService->parseUploadedFile($request->file('file'));
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Unable to parse the uploaded file. Use CSV, XLS, or XLSX format.',
            ], 422);
        }

        $prepared = $this->prepareImportRows($rows, $validated, $availabilityService);

        return response()->json([
            'message' => 'Import preview generated.',
            'data' => [
                'rows' => $prepared['rows'],
                'valid_count' => count($prepared['rows']) - count($prepared['errors']),
                'error_count' => count($prepared['errors']),
                'errors' => $prepared['errors'],
            ],
        ]);
    }

    public function importStore(Request $request, ScheduleImportService $importService, RoomAvailabilityService $availabilityService): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xls,xlsx'],
            'default_classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        ]);

        try {
            $rows = $importService->parseUploadedFile($request->file('file'));
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Unable to parse the uploaded file. Use CSV, XLS, or XLSX format.',
            ], 422);
        }

        $prepared = $this->prepareImportRows($rows, $validated, $availabilityService);
        if (! empty($prepared['errors'])) {
            return response()->json([
                'message' => 'Import has validation errors. Fix rows before saving.',
                'data' => [
                    'rows' => $prepared['rows'],
                    'valid_count' => count($prepared['rows']) - count($prepared['errors']),
                    'error_count' => count($prepared['errors']),
                    'errors' => $prepared['errors'],
                ],
            ], 422);
        }

        $createdCount = 0;

        DB::transaction(function () use (&$createdCount, $prepared, $availabilityService): void {
            foreach ($prepared['rows'] as $row) {
                $payload = $row['payload'];

                $scheduleConflict = $availabilityService->checkOfficialScheduleConflict(
                    (int) $payload['classroom_id'],
                    Carbon::parse($payload['start_at']),
                    Carbon::parse($payload['end_at']),
                    null,
                    true
                );

                if ($scheduleConflict['has_conflict']) {
                    throw ValidationException::withMessages([
                        'import' => ['Room is occupied by official schedule at selected time (row '.$row['row_number'].').'],
                    ]);
                }

                $reservationConflict = $availabilityService->checkReservationConflict(
                    (int) $payload['classroom_id'],
                    Carbon::parse($payload['start_at']),
                    Carbon::parse($payload['end_at']),
                    null,
                    true
                );

                if ($reservationConflict['has_conflict']) {
                    throw ValidationException::withMessages([
                        'import' => ['Room is already reserved at selected time (row '.$row['row_number'].').'],
                    ]);
                }

                Schedule::create($payload);
                $createdCount++;
            }
        });

        return response()->json([
            'message' => 'Schedule import completed successfully.',
            'data' => [
                'created_count' => $createdCount,
            ],
        ], 201);
    }

    public function show(int $id): View
    {
        $schedule = Schedule::query()
            ->with(['classroom', 'course.instructor'])
            ->whereHas('course.instructor', function ($scope): void {
                $this->applyItDepartmentScope($scope);
            })
            ->findOrFail($id);

        return view('frontend.admin.schedule-detail', [
            'schedule' => $schedule,
        ]);
    }

    public function store(StoreScheduleRequest $request, RoomAvailabilityService $availabilityService): RedirectResponse|JsonResponse
    {
        $payload = $request->validated();
        unset($payload['year_level']);

        if (isset($payload['instructor_user_id'], $payload['course_id']) && $payload['instructor_user_id'] !== null) {
            $course = Course::find((int) $payload['course_id']);
            if ($course && (int) $course->instructor_user_id !== (int) $payload['instructor_user_id']) {
                $course->instructor_user_id = (int) $payload['instructor_user_id'];
                $course->save();
            }
        }

        if (isset($payload['classroom_id'], $payload['course_id'])) {
            Course::query()
                ->whereKey((int) $payload['course_id'])
                ->update(['classroom_id' => (int) $payload['classroom_id']]);
        }

        $hasSemesterPattern = isset($payload['semester_start'], $payload['semester_end'])
            && isset($payload['day1'], $payload['day1_start'], $payload['day1_end'], $payload['day2'], $payload['day2_start'], $payload['day2_end']);

        if (isset($payload['semester_start']) && ! $hasSemesterPattern) {
            $message = 'Subject and room assignment saved. Add day and time details later to create room schedules.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 201);
            }

            return redirect()->route('admin.schedule')->with('status', $message);
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

            DB::transaction(function () use ($occurrences, $payload, $availabilityService): void {
                $seriesId = (string) Str::uuid();
                foreach ($occurrences as $occurrence) {
                    $scheduleConflict = $availabilityService->checkOfficialScheduleConflict(
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

                    $reservationConflict = $availabilityService->checkReservationConflict(
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
                }

                $now = now();
                $rows = [];

                foreach ($occurrences as $occurrence) {
                    $rows[] = [
                        'classroom_id' => $payload['classroom_id'],
                        'course_id' => $payload['course_id'],
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

            $createdCount = count($occurrences);
            $message = 'Recurring schedules created successfully ('.$createdCount.' sessions).';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'meta' => ['created_count' => $createdCount],
                ], 201);
            }

            return redirect()->route('admin.schedule')->with('status', $message);
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

        $createdSchedules = DB::transaction(function () use ($basePayload, $startAt, $endAt, $repeatUntil, $availabilityService) {
            $created = collect();

            $occurrenceStart = $startAt->copy();
            $occurrenceEnd = $endAt->copy();

            while ($repeatUntil === null || $occurrenceStart->lte($repeatUntil)) {
                $conflict = $availabilityService->checkOfficialScheduleConflict(
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

                $reservationConflict = $availabilityService->checkReservationConflict(
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

                $occurrencePayload = $basePayload;
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

        $createdCount = $createdSchedules->count();
        $primarySchedule = $createdSchedules->first();
        $message = $createdCount > 1
            ? 'Recurring schedules created successfully ('.$createdCount.' sessions).'
            : 'Schedule created successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $primarySchedule,
                'meta' => ['created_count' => $createdCount],
            ], 201);
        }

        return redirect()->route('admin.schedule')->with('status', $message);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule, RoomAvailabilityService $availabilityService): RedirectResponse|JsonResponse
    {
        $this->ensureItScheduleScope($schedule);

        $payload = $request->validated();
        $applyToSeries = $request->boolean('apply_to_series');
        $seriesId = $schedule->series_id;

        if ($applyToSeries && $seriesId) {
            $updatePayload = array_intersect_key($payload, array_flip([
                'classroom_id',
                'course_id',
                'status',
                'enrolled',
                'block_section',
            ]));

            DB::transaction(function () use ($seriesId, $updatePayload, $availabilityService): void {
                $seriesSchedules = Schedule::query()->where('series_id', $seriesId)->get();

                foreach ($seriesSchedules as $seriesSchedule) {
                    $targetClassroomId = (int) ($updatePayload['classroom_id'] ?? $seriesSchedule->classroom_id);
                    $startAt = $seriesSchedule->start_at;
                    $endAt = $seriesSchedule->end_at;

                    if ($startAt && $endAt) {
                        $conflict = $availabilityService->checkOfficialScheduleConflict(
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

                        $reservationConflict = $availabilityService->checkReservationConflict(
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

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Series updated successfully.',
                    'meta' => ['series_id' => $seriesId],
                ]);
            }

            return redirect()->route('admin.schedule')->with('status', 'Series updated successfully.');
        }
        $repeatUntil = isset($payload['repeat_until'])
            ? Carbon::parse((string) $payload['repeat_until'])->endOfDay()
            : null;
        unset($payload['repeat_until']);

        $effectiveStartAt = isset($payload['start_at']) ? Carbon::parse($payload['start_at']) : $schedule->start_at;
        $effectiveEndAt = isset($payload['end_at']) ? Carbon::parse($payload['end_at']) : $schedule->end_at;

        if ($effectiveStartAt) {
            $payload['day_of_week'] = $effectiveStartAt->dayOfWeek;
        }

        $createdCount = DB::transaction(function () use ($schedule, $payload, $effectiveStartAt, $effectiveEndAt, $repeatUntil, $availabilityService): int {
            $targetClassroomId = (int) ($payload['classroom_id'] ?? $schedule->classroom_id);

            $conflict = $availabilityService->checkOfficialScheduleConflict(
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

            $reservationConflict = $availabilityService->checkReservationConflict(
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
                    $loopConflict = $availabilityService->checkOfficialScheduleConflict(
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

                    $loopReservationConflict = $availabilityService->checkReservationConflict(
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

        $message = $createdCount > 0
            ? 'Schedule updated and '.$createdCount.' recurring session(s) created.'
            : 'Schedule updated successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $schedule,
                'meta' => ['created_count' => $createdCount],
            ]);
        }

        return redirect()->route('admin.schedule.show', $schedule->id)->with('status', $message);
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse|JsonResponse
    {
        $this->ensureItScheduleScope($schedule);

        $schedule->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Schedule deleted successfully.']);
        }

        return redirect()->route('admin.schedule')->with('status', 'Schedule deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'schedule_ids' => ['required', 'array', 'min:1'],
            'schedule_ids.*' => ['integer', 'distinct', 'exists:schedules,id'],
        ]);

        $requestedIds = collect($validated['schedule_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $schedules = Schedule::query()
            ->with('course.instructor')
            ->whereIn('id', $requestedIds)
            ->get();

        $allowedIds = $schedules
            ->filter(function (Schedule $schedule): bool {
                return app(RoomAvailabilityService::class)->isItUserDepartment($schedule->course?->instructor?->department);
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($allowedIds->count() !== $requestedIds->count()) {
            throw ValidationException::withMessages([
                'schedule_ids' => ['Some selected schedules are outside your allowed department scope.'],
            ]);
        }

        $deletedCount = Schedule::query()->whereIn('id', $allowedIds)->delete();
        $message = $deletedCount > 1
            ? $deletedCount.' schedules deleted successfully.'
            : 'Schedule deleted successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'meta' => ['deleted_count' => $deletedCount],
            ]);
        }

        return redirect()->route('admin.schedule')->with('status', $message);
    }

    private function ensureItScheduleScope(Schedule $schedule): void
    {
        $schedule->loadMissing('course.instructor');

        if (! app(RoomAvailabilityService::class)->isItUserDepartment($schedule->course?->instructor?->department)) {
            abort(404);
        }
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

    /**
     * @param array<int, array<string, string|null>> $rows
     * @param array<string, mixed> $defaults
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, array<string, mixed>>}
     */
    private function prepareImportRows(array $rows, array $defaults, RoomAvailabilityService $availabilityService): array
    {
        $classroomsById = Classroom::query()->get()->keyBy('id');
        $classroomsByName = Classroom::query()->get()->keyBy(function (Classroom $classroom): string {
            return strtolower(trim($classroom->name));
        });

        $courses = Course::query()
            ->with('instructor')
            ->whereHas('instructor', function ($scope): void {
                $this->applyItDepartmentScope($scope);
            })
            ->get();

        $coursesById = $courses->keyBy('id');
        $coursesByCode = $courses->keyBy(function (Course $course): string {
            return strtolower(trim((string) $course->code));
        });
        $coursesByTitle = $courses->groupBy(function (Course $course): string {
            return $this->normalizeLookup((string) $course->title);
        });
        $coursesByInstructorName = $courses->groupBy(function (Course $course): string {
            return $this->normalizeLookup((string) $course->instructor?->name);
        });
        $coursesByInstructorEmail = $courses->groupBy(function (Course $course): string {
            return strtolower(trim((string) $course->instructor?->email));
        });

        $itFacultyUsers = User::query()
            ->whereRaw("LOWER(COALESCE(role, '')) = ?", ['faculty'])
            ->where(function ($scope): void {
                $this->applyItDepartmentScope($scope);
            })
            ->get(['id', 'name', 'email']);

        $itFacultyByName = $itFacultyUsers->groupBy(function (User $faculty): string {
            return $this->normalizeLookup((string) $faculty->name);
        });
        $itFacultyByEmail = $itFacultyUsers->groupBy(function (User $faculty): string {
            return strtolower(trim((string) $faculty->email));
        });

        $preparedRows = [];
        $errors = [];

        foreach ($rows as $index => $rawRow) {
            $rowNumber = $index + 2;
            $rowErrors = [];

            $classroom = null;
            $roomId = $this->extractNullableInt($rawRow['classroom_id'] ?? $rawRow['room_id'] ?? null)
                ?? $this->extractNullableInt($defaults['default_classroom_id'] ?? null);

            if ($roomId !== null) {
                $classroom = $classroomsById->get($roomId);
            }

            if (! $classroom) {
                $roomName = trim((string) ($rawRow['classroom'] ?? $rawRow['room'] ?? $rawRow['classroom_name'] ?? $rawRow['room_name'] ?? ''));
                if ($roomName !== '') {
                    $classroom = $classroomsByName->get(strtolower($roomName));
                }
            }

            if (! $classroom) {
                $rowErrors[] = 'Classroom is required or not found.';
            }

            $course = null;
            $courseId = $this->extractNullableInt($rawRow['course_id'] ?? null);
            $instructorName = $this->normalizeLookup((string) ($rawRow['instructor'] ?? $rawRow['instructor_name'] ?? $rawRow['faculty'] ?? $rawRow['faculty_name'] ?? $rawRow['teacher'] ?? ''));
            $instructorEmail = strtolower(trim((string) ($rawRow['instructor_email'] ?? $rawRow['faculty_email'] ?? $rawRow['teacher_email'] ?? '')));

            if ($courseId !== null) {
                $course = $coursesById->get($courseId);
            }

            if (! $course) {
                $courseCode = strtolower(trim((string) ($rawRow['course_code'] ?? $rawRow['code'] ?? $rawRow['course'] ?? '')));
                if ($courseCode !== '') {
                    $course = $coursesByCode->get($courseCode);
                }
            }

            if (! $course) {
                $courseTitle = $this->normalizeLookup((string) ($rawRow['course_title'] ?? $rawRow['subject'] ?? $rawRow['title'] ?? $rawRow['course_name'] ?? ''));

                if ($courseTitle !== '') {
                    /** @var \Illuminate\Support\Collection<int, Course> $titleCandidates */
                    $titleCandidates = collect($coursesByTitle->get($courseTitle, collect()));

                    if ($instructorName !== '' || $instructorEmail !== '') {
                        $titleCandidates = $titleCandidates->filter(function (Course $candidate) use ($instructorName, $instructorEmail): bool {
                            $candidateName = $this->normalizeLookup((string) $candidate->instructor?->name);
                            $candidateEmail = strtolower(trim((string) $candidate->instructor?->email));

                            if ($instructorEmail !== '' && $candidateEmail === $instructorEmail) {
                                return true;
                            }

                            if ($instructorName !== '' && $candidateName === $instructorName) {
                                return true;
                            }

                            return false;
                        })->values();
                    }

                    if ($titleCandidates->count() === 1) {
                        $course = $titleCandidates->first();
                    } elseif ($titleCandidates->count() > 1) {
                        $rowErrors[] = 'Multiple courses matched this subject and instructor. Use course_code or course_id in import row.';
                    }
                }
            }

            if (! $course) {
                /** @var \Illuminate\Support\Collection<int, Course> $instructorCandidates */
                $instructorCandidates = collect();

                if ($instructorEmail !== '') {
                    $instructorCandidates = collect($coursesByInstructorEmail->get($instructorEmail, collect()));
                }

                if ($instructorName !== '') {
                    if ($instructorCandidates->isEmpty()) {
                        $instructorCandidates = collect($coursesByInstructorName->get($instructorName, collect()));
                    } else {
                        $instructorCandidates = $instructorCandidates
                            ->filter(function (Course $candidate) use ($instructorName): bool {
                                return $this->normalizeLookup((string) $candidate->instructor?->name) === $instructorName;
                            })
                            ->values();
                    }
                }

                if ($instructorCandidates->isNotEmpty()) {
                    $course = $instructorCandidates
                        ->sortBy(function (Course $candidate): string {
                            return strtolower((string) ($candidate->code ?? ''));
                        })
                        ->first();
                }
            }

            $startValue = trim((string) ($rawRow['start_at'] ?? $rawRow['start'] ?? $rawRow['start_datetime'] ?? ''));
            $endValue = trim((string) ($rawRow['end_at'] ?? $rawRow['end'] ?? $rawRow['end_datetime'] ?? ''));

            $startAt = null;
            $endAt = null;
            if ($startValue === '') {
                $rowErrors[] = 'Start datetime is required.';
            } else {
                try {
                    $startAt = Carbon::parse($startValue);
                } catch (Throwable $exception) {
                    $rowErrors[] = 'Start datetime format is invalid.';
                }
            }

            if ($endValue === '') {
                $rowErrors[] = 'End datetime is required.';
            } else {
                try {
                    $endAt = Carbon::parse($endValue);
                } catch (Throwable $exception) {
                    $rowErrors[] = 'End datetime format is invalid.';
                }
            }

            if ($startAt && $endAt && $endAt->lte($startAt)) {
                $rowErrors[] = 'End datetime must be after start datetime.';
            }

            $status = strtolower(trim((string) ($rawRow['status'] ?? 'scheduled')));
            if (! in_array($status, ['scheduled', 'ongoing', 'completed', 'cancelled'], true)) {
                $status = 'scheduled';
            }

            $enrolled = $this->extractNullableInt($rawRow['enrolled'] ?? null) ?? 0;

            $blockSection = trim((string) ($rawRow['block_section'] ?? $rawRow['block'] ?? $rawRow['section'] ?? ''));


            if ($rowErrors === [] && $classroom && $startAt && $endAt) {
                $scheduleConflict = $availabilityService->checkOfficialScheduleConflict(
                    (int) $classroom->id,
                    $startAt,
                    $endAt,
                    null,
                    false
                );

                if ($scheduleConflict['has_conflict']) {
                    $rowErrors[] = 'Room is occupied by official schedule at selected time.';
                }

                $reservationConflict = $availabilityService->checkReservationConflict(
                    (int) $classroom->id,
                    $startAt,
                    $endAt,
                    null,
                    false
                );

                if ($reservationConflict['has_conflict']) {
                    $rowErrors[] = 'Room is already reserved at selected time.';
                }
            }

            $payload = [
                'classroom_id' => $classroom?->id,
                'course_id' => $course?->id,
                'block_section' => $blockSection !== '' ? mb_substr($blockSection, 0, 64) : null,
                'start_at' => $startAt?->toDateTimeString(),
                'end_at' => $endAt?->toDateTimeString(),
                'status' => $status,
                'day_of_week' => $startAt?->dayOfWeek,
                'enrolled' => max(0, $enrolled),
            ];

            $preparedRows[] = [
                'row_number' => $rowNumber,
                'room' => $classroom?->name,
                'course' => $course?->code,
                'instructor' => $course?->instructor?->name
                    ?? trim((string) ($rawRow['instructor'] ?? $rawRow['instructor_name'] ?? $rawRow['faculty'] ?? $rawRow['faculty_name'] ?? $rawRow['teacher'] ?? '')),
                'subject' => $course?->title,
                'start_at' => $payload['start_at'],
                'end_at' => $payload['end_at'],
                'status' => $status,
                'enrolled' => $payload['enrolled'],
                'is_valid' => count($rowErrors) === 0,
                'errors' => $rowErrors,
                'payload' => $payload,
            ];

            if ($rowErrors !== []) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'messages' => $rowErrors,
                ];
            }
        }

        return [
            'rows' => $preparedRows,
            'errors' => $errors,
        ];
    }

    private function extractNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '' || ! ctype_digit($trimmed)) {
            return null;
        }

        return (int) $trimmed;
    }

    private function normalizeLookup(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
