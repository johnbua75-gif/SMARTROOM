<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuickStartAttendanceRequest;
use App\Http\Requests\StartFromCardAttendanceRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\StoreBulkAttendanceRecordsRequest;
use App\Http\Requests\StoreRecordAttendanceRequest;
use App\Models\AccessCard;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // Fetch only courses assigned to the current instructor
        $courses = Course::with('schedules.classroom')
            ->where('instructor_user_id', Auth::id())
            ->orderBy('code')
            ->get();

        $filterCourse = $request->query('course_id');
        $filterStatus = $request->query('status');
        $filterMonth = $request->query('month', now()->format('Y-m'));

        $q = AttendanceSession::query()->where('created_by', Auth::id());
        if ($filterCourse) {
            // attendance_sessions table doesn't have a reliable `course_id` column in some schemas,
            // filter by the schedule's course instead.
            $q->whereHas('schedule', fn ($qq) => $qq->where('course_id', $filterCourse));
        }
        if ($filterStatus) {
            $q->where('status', $filterStatus);
        }
        if ($filterMonth) {
            [$y, $m] = explode('-', $filterMonth);
            $q->whereYear('date', $y)->whereMonth('date', $m);
        }

        $sessions = $q
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $total = AttendanceSession::where('created_by', Auth::id())->count();
        $thisMonth = AttendanceSession::where('created_by', Auth::id())->whereYear('date', now()->year)->whereMonth('date', now()->month)->count();

        $overallRate = 0;
        $records = AttendanceRecord::whereHas('session', fn ($q) => $q->where('created_by', Auth::id()))->get();
        if ($records->count() > 0) {
            $attendedRecords = $records->filter(fn (AttendanceRecord $record): bool => (bool) $record->present || in_array($record->status, ['present', 'late'], true)
            )->count();
            $overallRate = round(100 * ($attendedRecords / $records->count()), 1);
        }

        $open = AttendanceSession::where('created_by', Auth::id())->where('status', 'open')->latest()->first();

        // attendance_sessions may not have a course_id column; derive course count from schedules
        $scheduleIds = AttendanceSession::where('created_by', Auth::id())->pluck('schedule_id')->filter()->unique()->toArray();
        $coursesCount = 0;
        if (! empty($scheduleIds)) {
            $coursesCount = Schedule::whereIn('id', $scheduleIds)->distinct('course_id')->count('course_id');
        }

        // Provide stats keys used by both views (attendance index and dashboard)
        $stats = [
            'total_sessions' => $total,
            'this_month' => $thisMonth,
            'overall_rate' => $overallRate,
            'rate' => $overallRate,
            'open_session' => $open,
            'courses' => $coursesCount,
        ];

        // Build assigned attendance cards from schedules
        $now = now();
        $facultyScheduleQuery = Schedule::whereHas('course', function ($q) {
            $q->where('instructor_user_id', Auth::id());
        });

        $attendanceCards = (clone $facultyScheduleQuery)
            ->with(['classroom', 'course'])
            ->orderBy('start_at')
            ->get()
            ->groupBy(function (Schedule $schedule): string {
                return implode('|', [
                    (string) ($schedule->course_id ?? 0),
                    (string) ($schedule->block_section ?? ''),
                    (string) ($schedule->classroom_id ?? 0),
                    (string) optional($schedule->start_at)->format('H:i'),
                ]);
            })
            ->map(function ($items) use ($now): array {
                $sorted = $items->sortBy('start_at')->values();
                $current = $sorted->first(function (Schedule $schedule) use ($now): bool {
                    return $schedule->start_at
                        && $schedule->start_at->lte($now)
                        && ($schedule->end_at === null || $schedule->end_at->gte($now));
                });
                $upcoming = $sorted->first(function (Schedule $schedule) use ($now): bool {
                    return $schedule->start_at && $schedule->start_at->gte($now);
                });
                $primary = $current ?? $upcoming ?? $sorted->last();

                if (! $primary) {
                    return [];
                }

                $status = 'finished';
                if ($current) {
                    $status = 'ongoing';
                } elseif ($upcoming) {
                    $status = 'upcoming';
                }

                $courseCode = (string) ($primary->course?->code ?? 'N/A');
                $courseTitle = (string) ($primary->course?->title ?? 'Untitled Subject');
                $roomName = (string) ($primary->classroom?->name ?? 'Room N/A');
                $building = (string) ($primary->classroom?->building ?? '');
                $section = (string) ($primary->block_section ?? '-');

                return [
                    'schedule_id' => (int) $primary->id,
                    'course_code' => $courseCode,
                    'subject' => $courseTitle,
                    'section' => $section,
                    'room' => trim($roomName.($building !== '' ? ', '.$building : '')),
                    'time' => $primary->start_at
                        ? $primary->start_at->format('g:i A').($primary->end_at ? ' - '.$primary->end_at->format('g:i A') : '')
                        : 'TBA',
                    'status' => $status,
                    'search' => strtolower(trim($courseCode.' '.$courseTitle.' '.$section.' '.$roomName.' '.$building)),
                ];
            })
            ->filter()
            ->values();

        // Render the attendance index view
        return view('frontend.faculty.attendance', compact('courses', 'sessions', 'stats', 'attendanceCards', 'filterCourse', 'filterStatus', 'filterMonth'));
    }

    public function store(StoreAttendanceRequest $request)
    {
        $validated = $request->validated();

        $schedule = null;
        $scheduleId = $validated['schedule_id'] ?? null;
        if ($scheduleId) {
            $schedule = Schedule::find($scheduleId);
            if (! $schedule) {
                return redirect()->route('faculty.attendance')->with('error', 'Schedule not found.');
            }

            if (($schedule->course?->instructor_user_id ?? null) !== Auth::id()) {
                return redirect()->route('faculty.attendance')->with('error', 'You are not allowed to open this schedule.');
            }
        }

        // Accept optional course selection for ad-hoc sessions
        $courseId = $validated['course_id'] ?? null;

        $sessionDate = $validated['session_date'];

        if ($schedule) {
            [$session, $message] = $this->openOrResumeScheduleSession($schedule, (string) $request->input('remarks'));

            return redirect()
                ->route('faculty.attendance.session', $session->id)
                ->with('success', $message);
        }

        $token = Str::upper(Str::random(8));

        $session = AttendanceSession::create([
            'schedule_id' => null,
            'classroom_id' => $request->input('classroom_id') ?? null,
            'room' => $request->input('room') ?? null,
            'session_date' => $sessionDate,
            'date' => $sessionDate,
            'started_at' => $request->input('started_at') ? date('H:i:s', strtotime($request->input('started_at'))) : now()->format('H:i:s'),
            'status' => 'open',
            'remarks' => $request->input('remarks'),
            'created_by' => Auth::id(),
            'faculty_user_id' => Auth::id(),
            'token' => $token,
            'expires_at' => now()->addMinutes(60),
            'course_id' => Schema::hasColumn('attendance_sessions', 'course_id') ? ($courseId ?? null) : null,
        ]);

        return redirect()->route('faculty.attendance.session', $session->id)->with('success', 'Attendance session opened.');
    }

    public function showSession($id)
    {
        $session = AttendanceSession::with([
            'records' => fn ($query) => $query->orderBy('student_name'),
            'schedule.course',
            'schedule.classroom',
            'course',
        ])->find($id);
        if (! $session) {
            return redirect()->route('faculty.attendance')->with('error', 'Session not found.');
        }

        if ((int) ($session->created_by ?? 0) !== (int) Auth::id()) {
            abort(403, 'You are not allowed to view this session.');
        }

        if ($session->status === 'open' && $session->schedule) {
            $this->ensureSessionRoster($session, $session->schedule);
            $session->load(['records' => fn ($query) => $query->orderBy('student_name')]);
        }

        $course = $session->course ?: $session->schedule?->course;
        $classroom = $session->schedule?->classroom;

        $records = $session->records ?? collect();
        $presentRecords = $records->filter(fn (AttendanceRecord $record): bool => (bool) $record->present || in_array($record->status, ['present', 'late'], true)
        );
        $stats = [
            'total' => $records->count(),
            'present' => $presentRecords->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'excused' => $records->where('status', 'excused')->count(),
            'rate' => $records->count() > 0
                ? round(100 * ($presentRecords->count() / $records->count()), 1)
                : 0,
        ];

        $sessionView = array_merge($session->toArray(), [
            'course_code' => $course?->code ?? $course?->subject_code ?? $course?->title ?? 'Session',
            'course_title' => $course?->title ?? $course?->name ?? '',
            'date_short' => $session->date ? Carbon::parse((string) $session->date)->format('M j, Y') : '',
            'room' => $session->room ?: ($classroom?->name ?? null),
            'building' => $classroom?->building ?? null,
        ]);

        if (request()->query('modal') === '1') {
            return view('frontend.faculty._attendance-session-content', [
                'session' => $sessionView,
                'records' => $records,
                'stats' => $stats,
            ]);
        }

        return view('frontend.faculty.attendance-session', [
            'session' => $sessionView,
            'records' => $records,
            'stats' => $stats,
        ]);
    }

    /**
     * Show a QR/checkin view for faculty to display the session QR
     */
    public function showQr($id)
    {
        $session = AttendanceSession::query()
            ->whereKey($id)
            ->where('created_by', Auth::id())
            ->first();
        if (! $session) {
            return redirect()->route('faculty.attendance')->with('error', 'Session not found');
        }

        $checkinUrl = route('attendance.checkin.show', $session->token);

        return view('frontend.faculty.attendance-qr', compact('session', 'checkinUrl'));
    }

    public function sessionStatus(Request $request, int $id)
    {
        $session = AttendanceSession::query()
            ->with('schedule')
            ->whereKey($id)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        if ($session->status === 'open' && $session->schedule) {
            $this->ensureSessionRoster($session, $session->schedule);
        }

        $records = AttendanceRecord::query()
            ->where('attendance_session_id', $session->id)
            ->get(['status', 'present', 'time_in']);
        $checkedIn = $records->whereNotNull('time_in')->count();
        $present = $records->whereIn('status', ['present', 'late'])->count();

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'status' => $session->status,
            'total' => $records->count(),
            'checked_in' => $checkedIn,
            'present' => $present,
            'late' => $records->where('status', 'late')->count(),
            'absent' => $records->where('status', 'absent')->count(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function liveOverview(Request $request)
    {
        $userId = $request->user()->id;
        $overview = Cache::remember('faculty:attendance-overview:v1:'.$userId, now()->addSeconds(5), function () use ($userId): array {
            $sessions = AttendanceSession::query()
                ->where('created_by', $userId)
                ->get(['id', 'status']);

            $checkedIn = AttendanceRecord::query()
                ->whereIn('attendance_session_id', $sessions->pluck('id'))
                ->whereNotNull('time_in')
                ->select('attendance_session_id')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('attendance_session_id')
                ->pluck('count', 'attendance_session_id');

            return [
                'success' => true,
                'open_sessions' => $sessions->where('status', 'open')->count(),
                'closed_sessions' => $sessions->where('status', 'closed')->count(),
                'sessions' => $sessions->map(fn (AttendanceSession $session): array => [
                    'id' => $session->id,
                    'status' => $session->status,
                    'checked_in' => (int) ($checkedIn[$session->id] ?? 0),
                ])->values()->all(),
            ];
        });

        return response()->json($overview)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function studentRoster(int $id)
    {
        $session = AttendanceSession::query()
            ->with(['schedule.course', 'course'])
            ->whereKey($id)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        $records = AttendanceRecord::query()
            ->where('attendance_session_id', $session->id)
            ->orderBy('student_name')
            ->get();

        $studentIds = $records->pluck('student_id')->filter()->map(fn ($id): int => (int) $id);
        $studentNumbers = $records->pluck('student_id_number')->filter()->values();
        $students = Student::query()
            ->where(function ($query) use ($studentIds, $studentNumbers): void {
                $query->whereIn('id', $studentIds)
                    ->orWhereIn('student_id', $studentNumbers);
            })
            ->get(['id', 'student_id', 'block_section'])
            ->keyBy('id');
        $studentsByNumber = $students->keyBy('student_id');
        $defaultBlock = $session->schedule?->block_section;

        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'course' => $session->course?->code ?? $session->schedule?->course?->code ?? 'Attendance Session',
                'date' => $session->date ? Carbon::parse((string) $session->date)->format('M j, Y') : '-',
            ],
            'students' => $records->map(function (AttendanceRecord $record) use ($students, $studentsByNumber, $defaultBlock): array {
                $student = $students->get((int) $record->student_id)
                    ?? $studentsByNumber->get($record->student_id_number);
                $status = strtolower((string) $record->status);

                return [
                    'name' => $record->student_name,
                    'student_id' => $record->student_id_number ?? $student?->student_id ?? '-',
                    'block' => $student?->block_section ?? $defaultBlock ?? 'Unassigned',
                    'status' => $status ?: ($record->present ? 'present' : 'absent'),
                    'checked_in' => $record->time_in !== null,
                    'time_in' => $record->time_in?->format('g:i A'),
                ];
            })->values(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Show student-facing checkin page (GET). Students must be authenticated.
     */
    public function showCheckin(Request $request, $token)
    {
        $session = AttendanceSession::where('token', $token)->first();
        if (! $session) {
            abort(404, 'Session not found or closed');
        }

        $checkinUnavailable = $session->status !== 'open'
            || ($session->expires_at && now()->greaterThan($session->expires_at));

        return view('frontend.student.checkin', compact('session', 'checkinUnavailable'));
    }

    /**
     * Attendance dashboard view (faculty)
     */
    public function dashboard(Request $request)
    {
        $q = AttendanceSession::query()->where('created_by', Auth::id());
        $sessions = $q->orderBy('date', 'desc')->get();

        $total = AttendanceSession::where('created_by', Auth::id())->count();
        $thisMonth = AttendanceSession::where('created_by', Auth::id())->whereYear('date', now()->year)->whereMonth('date', now()->month)->count();

        $records = AttendanceRecord::whereHas('session', fn ($q) => $q->where('created_by', Auth::id()))->get();
        $rate = 0;
        if ($records->count() > 0) {
            $attendedRecords = $records->filter(fn (AttendanceRecord $record): bool => (bool) $record->present || in_array($record->status, ['present', 'late'], true)
            )->count();
            $rate = round(100 * ($attendedRecords / $records->count()), 1);
        }

        $scheduleIds = AttendanceSession::where('created_by', Auth::id())->pluck('schedule_id')->filter()->unique()->toArray();
        $coursesCount = 0;
        if (! empty($scheduleIds)) {
            $coursesCount = Schedule::whereIn('id', $scheduleIds)->distinct('course_id')->count('course_id');
        }

        $stats = ['total_sessions' => $total, 'this_month' => $thisMonth, 'rate' => $rate, 'courses' => $coursesCount];

        return view('frontend.faculty.attendance-dashboard', compact('sessions', 'stats'));
    }

    public function storeRecord(StoreRecordAttendanceRequest $request, $id)
    {
        $request->validated();

        $session = AttendanceSession::query()
            ->whereKey($id)
            ->where('created_by', Auth::id())
            ->first();
        if (! $session) {
            return redirect()->back()->with('error', 'Session not found.');
        }

        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_name' => $request->input('student_name'),
            'student_id' => $request->input('student_id'),
            'present' => $request->input('present') == '1',
            'remarks' => $request->input('remarks'),
        ]);

        return redirect()->route('faculty.attendance.session', $session->id)->with('success', 'Attendance recorded.');
    }

    // Accepts bulk JSON records payload: { records: [ { student_name, student_id_number, status, time_in, remarks }, ... ] }
    public function storeRecordsBulk(StoreBulkAttendanceRecordsRequest $request, $id)
    {
        $session = AttendanceSession::query()
            ->whereKey($id)
            ->where('created_by', Auth::id())
            ->first();
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        $payload = $request->validated()['records'];

        // Replace existing records for the session with provided ones
        DB::transaction(function () use ($payload, $session) {
            AttendanceRecord::where('attendance_session_id', $session->id)->delete();
            foreach ($payload as $r) {
                $name = trim($r['student_name'] ?? '');
                $status = $r['status'] ?? 'present';
                $present = in_array($status, ['present', 'late']);
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'student_name' => $name,
                    'student_id' => $r['student_id_number'] ?? $r['student_id'] ?? null,
                    'student_id_number' => $r['student_id_number'] ?? $r['student_id'] ?? null,
                    'status' => $status,
                    'time_in' => $r['time_in'] ?? null,
                    'present' => $present,
                    'remarks' => $r['remarks'] ?? null,
                ]);
            }
        });

        return response()->json(['success' => true]);
    }

    // Close a session (AJAX)
    public function close(Request $request, $id)
    {
        $session = AttendanceSession::query()
            ->whereKey($id)
            ->where('created_by', Auth::id())
            ->first();
        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }
        $session->status = 'closed';
        $session->ended_at = now()->format('H:i:s');
        $session->save();

        return response()->json(['success' => true]);
    }

    public function export($id)
    {
        $session = AttendanceSession::query()
            ->with('records')
            ->whereKey($id)
            ->where('created_by', Auth::id())
            ->first();
        if (! $session) {
            return redirect()->route('faculty.attendance')->with('error', 'Session not found.');
        }

        $filename = 'attendance_export_'.$session->id.'.csv';
        $rows = [];
        $rows[] = ['Date', 'Course', 'Room', 'Session Status', 'Student', 'Student ID', 'Attendance Status', 'Present', 'Remarks'];
        foreach ($session->records as $r) {
            $courseLabel = optional($session->course)->code ?? $session->course_id;
            $roomLabel = $session->room ?? (optional($session->classroom)->name ?? $session->classroom_id ?? '');
            $rows[] = [$session->date, $courseLabel, $roomLabel, $session->status, $r->student_name, $r->student_id_number ?? $r->student_id, $r->status, $r->present ? '1' : '0', $r->remarks];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Student check-in using session token (QR or link).
     */
    public function studentCheckin(Request $request, $token)
    {
        $session = AttendanceSession::where('token', $token)
            ->where('status', 'open')
            ->first();

        if (! $session) {
            return response()->json(['message' => 'Session not found or closed'], 404);
        }

        // check expiry if column exists
        if (isset($session->expires_at) && $session->expires_at && now()->greaterThan($session->expires_at)) {
            return response()->json(['message' => 'Session expired'], 410);
        }

        $user = Auth::user();
        $student = $user->student ?? null;
        if (! $student || $student->status !== 'active') {
            return response()->json([
                'message' => $student ? 'Student profile is inactive' : 'Student profile not found',
            ], 403);
        }

        // Only enrolled students should be able to check in.
        $courseId = $session->course_id ?: optional($session->schedule)->course_id;
        if ($courseId) {
            $isEnrolled = Enrollment::where('student_id', $student->id)
                ->where('course_id', $courseId)
                ->whereIn('status', ['active', 'enrolled'])
                ->exists();

            if (! $isEnrolled) {
                return response()->json(['message' => 'You are not enrolled in this class'], 403);
            }
        }

        // prevent duplicate
        $exists = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where(function ($q) use ($student) {
                $q->where('student_id', $student->id)->orWhere('student_id_number', $student->student_id);
            })->first();
        if ($exists && $exists->present) {
            return response()->json(['message' => 'Already checked in'], 200);
        }

        $startReference = $session->started_at
            ? Carbon::parse(($session->date ?? now()->toDateString()).' '.$session->started_at)
            : $session->created_at;
        $minutesLate = $startReference ? $startReference->diffInMinutes(now(), false) : 0;
        $status = $minutesLate <= 5 ? 'present' : 'late';

        $recordData = [
            'student_name' => $student->name,
            'student_id' => $student->id,
            'student_id_number' => $student->student_id,
            'status' => $status,
            'time_in' => now(),
            'present' => true,
        ];

        if ($exists) {
            $exists->update($recordData);
        } else {
            AttendanceRecord::create([
                'attendance_session_id' => $session->id,
                ...$recordData,
            ]);
        }

        return response()->json(['message' => 'Checked in', 'status' => $status], $exists ? 200 : 201);
    }

    /**
     * Get instructor's assigned courses with real-time status and upcoming schedules
     */
    public function getInstructorCourses(Request $request)
    {
        $userId = Auth::id();
        $now = Carbon::now();

        // Get all courses assigned to this instructor
        $courses = Course::where('instructor_user_id', $userId)
            ->with(['schedules' => function ($query) use ($now) {
                // Get schedules for today and upcoming
                $query->where('start_at', '>=', $now->copy()->startOfDay())
                    ->orderBy('start_at', 'asc');
            }, 'schedules.classroom'])
            ->orderBy('title')
            ->get();

        $coursesData = $courses->map(function ($course) use ($now) {
            // Get the next upcoming schedule for this course
            $nextSchedule = $course->schedules->first();

            if (! $nextSchedule) {
                return null;
            }

            // Determine status: Ongoing, Upcoming, Finished
            $startTime = $nextSchedule->start_at;
            $endTime = $nextSchedule->end_at;
            $status = 'upcoming';

            if ($startTime && $startTime <= $now && ($endTime === null || $endTime > $now)) {
                $status = 'ongoing';
            } elseif ($endTime && $endTime <= $now) {
                $status = 'finished';
            }

            // Check if session already exists for this schedule today
            $existingSession = AttendanceSession::where('schedule_id', $nextSchedule->id)
                ->whereDate('date', $nextSchedule->start_at->toDateString())
                ->where('status', 'open')
                ->first();

            return [
                'id' => $course->id,
                'code' => $course->code,
                'title' => $course->title,
                'section' => $nextSchedule->block_section ?? 'N/A',
                'room' => $nextSchedule->classroom?->name ?? 'TBA',
                'building' => $nextSchedule->classroom?->building ?? null,
                'schedule_id' => $nextSchedule->id,
                'schedule_start' => $startTime ? $startTime->format('g:i A') : 'TBA',
                'schedule_time' => $startTime && $endTime
                    ? $startTime->format('g:i A').' - '.$endTime->format('g:i A')
                    : 'TBA',
                'status' => $status,
                'enrolled' => $nextSchedule->enrolled ?? 0,
                'has_session' => (bool) $existingSession,
                'session_id' => $existingSession?->id,
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'courses' => $coursesData,
            'count' => $coursesData->count(),
        ]);
    }

    /**
     * Quick attendance start: Auto-create or resume session
     * Prevents duplicate sessions for the same schedule and day
     */
    public function quickAttendanceStart(QuickStartAttendanceRequest $request)
    {
        try {
            $request->validated();

            $userId = Auth::id();
            $scheduleId = $request->input('schedule_id');
            $schedule = Schedule::with('course', 'classroom')->find($scheduleId);

            if (! $schedule) {
                return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
            }

            // Verify this schedule belongs to the current instructor
            if ($schedule->course->instructor_user_id !== $userId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            [$session, $message, $created] = $this->openOrResumeScheduleSession($schedule);

            if (! $session || ! $session->id) {
                return response()->json(['success' => false, 'message' => 'Failed to create attendance session'], 500);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'session_id' => $session->id,
                'checkin_url' => route('attendance.checkin.show', $session->token),
                'qr_url' => 'https://quickchart.io/qr?text='.urlencode(route('attendance.checkin.show', $session->token)).'&size=320',
                'redirect_url' => route('faculty.attendance.session', $session->id),
                'created' => $created,
            ]);
        } catch (\Exception $e) {
            Log::error('quickAttendanceStart error: '.$e->getMessage(), [
                'schedule_id' => $request->input('schedule_id'),
                'user_id' => Auth::id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Server error: '.$e->getMessage()], 500);
        }
    }

    /**
     * Start attendance session when faculty taps card (card reader webhook).
     * Expects: card_number, optional schedule_id
     */
    public function startFromCard(StartFromCardAttendanceRequest $request)
    {
        $request->validated();

        // optional secret header validation
        $hookSecret = env('CARD_HOOK_SECRET');
        if ($hookSecret) {
            $provided = $request->header('X-CARD-HOOK-SECRET');
            if (! hash_equals($hookSecret, (string) $provided)) {
                return response()->json(['message' => 'Invalid hook secret'], 403);
            }
        }
        $card = AccessCard::where('card_number', $request->input('card_number'))->first();
        if (! $card || ! $card->user) {
            return response()->json(['message' => 'Access card not recognized'], 404);
        }

        $user = $card->user;
        // ensure user is faculty
        if ($user->role !== 'faculty') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $schedule = null;
        if ($request->filled('schedule_id')) {
            $schedule = Schedule::find($request->input('schedule_id'));
        }

        if ($schedule && $schedule->course?->instructor_user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($schedule) {
            [$session, $message] = $this->openOrResumeScheduleSession($schedule, 'Started by card: '.$card->card_number);

            return response()->json([
                'message' => $message,
                'session_id' => $session->id,
                'token' => $session->token,
                'checkin_url' => route('attendance.checkin', $session->token),
                'expires_at' => $session->expires_at,
            ], 201);
        }

        $token = Str::upper(Str::random(8));
        $session = AttendanceSession::create([
            'schedule_id' => $schedule?->id,
            'room' => $schedule?->classroom?->name ?? $card->classroom?->name ?? null,
            'date' => now()->format('Y-m-d'),
            'started_at' => now()->format('H:i:s'),
            'status' => 'open',
            'remarks' => 'Started by card: '.$card->card_number,
            'created_by' => $user->id,
            'faculty_user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addMinutes(60),
        ]);

        return response()->json([
            'message' => 'Session started',
            'session_id' => $session->id,
            'token' => $session->token,
            'checkin_url' => route('attendance.checkin', $session->token),
            'expires_at' => $session->expires_at,
        ], 201);
    }

    private function openOrResumeScheduleSession(Schedule $schedule, ?string $remarks = null): array
    {
        try {
            $today = now()->toDateString();
            $userId = Auth::id();

            return DB::transaction(function () use ($schedule, $today, $userId, $remarks): array {
                $existingSession = AttendanceSession::query()
                    ->where('schedule_id', $schedule->id)
                    ->whereDate('date', $today)
                    ->where('status', 'open')
                    ->where('created_by', $userId)
                    ->lockForUpdate()
                    ->first();

                if ($existingSession) {
                    $this->ensureSessionRoster($existingSession, $schedule);

                    return [$existingSession, 'Attendance session resumed successfully.', false];
                }

                $sessionData = [
                    'schedule_id' => $schedule->id,
                    'date' => $today,
                    'started_at' => now()->format('H:i:s'),
                    'status' => 'open',
                    'remarks' => $remarks,
                    'created_by' => $userId,
                ];

                $optionalSessionData = [
                    'course_id' => $schedule->course_id,
                    'classroom_id' => $schedule->classroom_id,
                    'room' => $schedule->classroom?->name ?? null,
                    'session_date' => $today,
                    'faculty_user_id' => $userId,
                    'token' => Str::upper(Str::random(8)),
                    'expires_at' => now()->addMinutes(60),
                ];

                foreach ($optionalSessionData as $column => $value) {
                    if (Schema::hasColumn('attendance_sessions', $column)) {
                        $sessionData[$column] = $value;
                    }
                }

                $session = AttendanceSession::create($sessionData);

                $this->ensureSessionRoster($session, $schedule);

                return [$session, 'Attendance session started successfully.', true];
            });
        } catch (\Exception $e) {
            Log::error('openOrResumeScheduleSession failed: '.$e->getMessage(), [
                'schedule_id' => $schedule->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function ensureSessionRoster(AttendanceSession $session, Schedule $schedule): void
    {
        $courseId = $schedule->course_id ?? $session->course_id ?? null;

        if (! $courseId) {
            return;
        }

        $enrollments = Enrollment::query()
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'enrolled'])
            ->with('student')
            ->get();

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (! $student) {
                continue;
            }

            AttendanceRecord::firstOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'student_id' => $student->id,
                ],
                [
                    'student_name' => $student->name,
                    'student_id_number' => $student->student_id ?? null,
                    'status' => 'absent',
                    'present' => false,
                ]
            );
        }
    }

    public function searchStudents(Request $request)
    {
        $email = $request->query('email');
        if (! is_string($email) || strlen(trim($email)) < 2) {
            return response()->json(['success' => false, 'students' => []]);
        }
        $email = trim($email);

        try {
            // Search for users with matching email (typically students)
            $students = User::query()
                ->whereRaw('LOWER(email) LIKE ?', ['%'.strtolower($email).'%'])
                ->where(function ($q) {
                    // Include users with role 'student' or no specific restriction
                    $q->where('role', 'student')->orWhereNull('role');
                })
                ->select('id', 'name', 'email', 'student_id')
                ->limit(10)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->student_id ?? $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                })
                ->values();

            return response()->json(['success' => true, 'students' => $students]);
        } catch (\Exception $e) {
            Log::error('searchStudents error: '.$e->getMessage());

            return response()->json(['success' => false, 'students' => [], 'error' => $e->getMessage()], 500);
        }
    }
}
