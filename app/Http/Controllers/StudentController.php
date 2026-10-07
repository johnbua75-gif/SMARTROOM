<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Student;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Show the student home dashboard.
     */
    public function home(RoomAvailabilityService $availabilityService)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        // Check or create student record
        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'student_id' => Student::generateStudentId(),
                'status' => 'active',
            ]
        );

        $enrolledCourseIds = $student->courses()
            ->wherePivotIn('status', ['active', 'enrolled'])
            ->pluck('courses.id');

        // Only show classes belonging to courses this student is enrolled in.
        $todaySchedules = Schedule::query()
            ->whereIn('course_id', $enrolledCourseIds)
            ->whereNotNull('start_at')
            ->whereDate('start_at', today())
            ->with(['course.instructor', 'classroom'])
            ->orderBy('start_at')
            ->get();

        $todayClassesCount = $todaySchedules->count();

        $nextClass = $todaySchedules->first(
            fn ($schedule) => $schedule->start_at instanceof CarbonInterface
                && $schedule->start_at->greaterThan(now())
        );

        $nextClassTime = $nextClass
            ? Carbon::parse($nextClass->start_at)->format('g:i A')
            : 'N/A';

        $classrooms = Classroom::query()->get();
        $availableRoomsCount = $availabilityService
            ->buildRoomStatuses($classrooms, now(), now()->copy()->addHour())
            ->where('status', 'available')
            ->count();

        $studentNotifications = Notification::query()
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->latest()
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        // Pass data to view
        return view('frontend.student.home', compact('student', 'todaySchedules', 'todayClassesCount', 'nextClassTime', 'availableRoomsCount', 'studentNotifications'));
    }

    public function homeSummary(RoomAvailabilityService $availabilityService): JsonResponse
    {
        $userId = Auth::id();
        $summary = Cache::remember('student:home-summary:v1:'.$userId, now()->addSeconds(15), function () use ($userId, $availabilityService): array {
            $student = Student::query()->where('user_id', $userId)->firstOrFail();
            $enrolledCourseIds = $student->courses()
                ->wherePivotIn('status', ['active', 'enrolled'])
                ->pluck('courses.id');
            $todaySchedules = Schedule::query()
                ->whereIn('course_id', $enrolledCourseIds)
                ->whereNotNull('start_at')
                ->whereDate('start_at', today())
                ->orderBy('start_at')
                ->get(['start_at']);

            $nextClass = $todaySchedules->first(
                fn (Schedule $schedule): bool => $schedule->start_at?->greaterThan(now()) ?? false
            );
            $classrooms = Classroom::query()->get();
            $availableRoomsCount = $availabilityService
                ->buildRoomStatuses($classrooms, now(), now()->copy()->addHour())
                ->where('status', 'available')
                ->count();

            $records = AttendanceRecord::query()
                ->where(function ($query) use ($student): void {
                    $query->where('student_id', $student->id)
                        ->orWhere('student_id_number', $student->student_id);
                })
                ->whereHas('session', fn ($query) => $query->where('status', 'closed'))
                ->get(['time_in']);
            $attended = $records->whereNotNull('time_in')->count();
            $total = $records->count();

            return [
                'success' => true,
                'today_classes' => $todaySchedules->count(),
                'next_class' => $nextClass?->start_at?->format('g:i A') ?? 'N/A',
                'available_rooms' => $availableRoomsCount,
                'attended' => $attended,
                'attendance_rate' => $total > 0 ? round(($attended / $total) * 100, 1) : 0,
            ];
        });

        return response()->json($summary)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Show checking room details.
     */
    public function checkingRoom()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $student = Student::where('user_id', $user->id)->first();

        $classrooms = Classroom::all();

        return view('frontend.student.checkingRoom', compact('student', 'classrooms'));
    }

    public function courses(): View
    {
        $user = Auth::user();

        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'student_id' => Student::generateStudentId(),
                'status' => 'active',
            ]
        );

        $courses = Course::query()
            ->with('instructor')
            ->orderBy('code')
            ->orderBy('title')
            ->get();

        $enrollments = $student->enrollments()->get(['course_id', 'status']);
        $enrollmentStatuses = $enrollments->pluck('status', 'course_id');
        $enrolledCourseIds = $enrollments
            ->whereIn('status', ['active', 'enrolled'])
            ->pluck('course_id')
            ->all();

        return view('frontend.student.courses', compact('student', 'courses', 'enrolledCourseIds', 'enrollmentStatuses'));
    }

    public function requestEnrollment(Course $course): RedirectResponse
    {
        $user = Auth::user();

        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'student_id' => Student::generateStudentId(),
                'status' => 'active',
            ]
        );

        if ($student->status !== 'active') {
            return to_route('student.courses')->with('error', 'Your student profile is inactive. Contact the administrator.');
        }

        if (! $course->instructor_user_id) {
            return to_route('student.courses')->with('error', 'This course has no assigned instructor yet. Contact the administrator.');
        }

        $requestState = DB::transaction(function () use ($course, $student): string {
            $enrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->lockForUpdate()
                ->first();

            if ($enrollment && in_array($enrollment->status, ['active', 'enrolled'], true)) {
                return 'active';
            }

            if ($enrollment?->status === 'pending') {
                return 'pending';
            }

            if ($enrollment && $enrollment->status !== 'rejected') {
                return 'restricted';
            }

            if ($enrollment) {
                $enrollment->update([
                    'enrolled_at' => null,
                    'status' => 'pending',
                ]);
            } else {
                $enrollment = Enrollment::create([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'enrolled_at' => null,
                    'status' => 'pending',
                ]);
            }

            Notification::create([
                'type' => 'enrollment_request',
                'title' => 'Student enrollment request',
                'body' => $student->name.' requested enrollment in '.$course->code.' - '.$course->title.'.',
                'data' => [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'student_id_number' => $student->student_id,
                    'student_email' => $student->email,
                    'course_id' => $course->id,
                    'course_code' => $course->code,
                ],
                'user_id' => $course->instructor_user_id,
            ]);

            return 'requested';
        });

        $message = match ($requestState) {
            'active' => 'You are already enrolled in this course.',
            'pending' => 'Your enrollment request is already awaiting faculty review.',
            'restricted' => 'This enrollment is managed by your administrator.',
            default => 'Your request was sent to the assigned faculty member.',
        };

        return to_route('student.courses')->with($requestState === 'restricted' ? 'error' : 'success', $message);
    }

    public function enrolledCourses(): View
    {
        $student = Student::query()->where('user_id', Auth::id())->firstOrFail();
        $courses = $student->courses()
            ->wherePivotIn('status', ['active', 'enrolled'])
            ->with('instructor')
            ->orderBy('code')
            ->orderBy('title')
            ->get();

        return view('frontend.student.enrolled-courses', compact('student', 'courses'));
    }

    public function courseOverview(Course $course): View
    {
        $student = Student::query()->where('user_id', Auth::id())->firstOrFail();
        abort_unless(
            $student->courses()
                ->wherePivotIn('status', ['active', 'enrolled'])
                ->whereKey($course->id)
                ->exists(),
            404
        );

        $course->load('instructor');
        $schedules = Schedule::query()
            ->where('course_id', $course->id)
            ->with('classroom')
            ->orderBy('start_at')
            ->get();
        $attendanceRecords = AttendanceRecord::query()
            ->whereHas('session', fn ($query) => $query->where('course_id', $course->id))
            ->with('session')
            ->where(function ($query) use ($student): void {
                $query->where('student_id', $student->id)
                    ->orWhere('student_id_number', $student->student_id);
            })
            ->latest()
            ->get();

        return view('frontend.student.course-overview', compact('student', 'course', 'schedules', 'attendanceRecords'));
    }

    /**
     * Show student schedule.
     */
    public function schedule()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $student = Student::where('user_id', $user->id)->first();

        // Fetch only schedules for courses the student is enrolled in
        $enrolledCourseIds = $student?->courses()
            ->wherePivotIn('status', ['active', 'enrolled'])
            ->pluck('courses.id') ?? collect();
        $hasEnrolledCourses = $enrolledCourseIds->isNotEmpty();

        $schedules = $hasEnrolledCourses
            ? Schedule::whereIn('course_id', $enrolledCourseIds)
                ->whereNotNull('start_at')
                ->with(['course', 'classroom'])
                ->orderBy('start_at')
                ->get()
            : collect([]);

        return view('frontend.student.schedule', compact('student', 'schedules', 'hasEnrolledCourses'));
    }

    /**
     * Show the studentSchedule view (legacy "studentSchedule" page).
     */
    public function studentSchedule(): RedirectResponse
    {
        return to_route('student.schedule');
    }

    /**
     * Show attendance page.
     */
    public function attendance()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'student_id' => Student::generateStudentId(),
                'status' => 'active',
            ]
        );

        // Fetch attendance records (match by student PK or student id number)
        $attendanceRecords = AttendanceRecord::with('session.course')->where(function ($q) use ($student) {
            $q->where('student_id', optional($student)->id)
                ->orWhere('student_id_number', optional($student)->student_id);
        })->whereHas('session', fn ($query) => $query->where('status', 'closed'))
            ->orderBy('created_at', 'desc')->get();

        // Calculate stats
        $totalAttended = $attendanceRecords->filter(fn (AttendanceRecord $record): bool => (bool) $record->present
            || in_array(strtolower((string) $record->status), ['present', 'late'], true)
        )->count();
        $totalAbsent = $attendanceRecords->filter(fn (AttendanceRecord $record): bool => ! $record->present
            && strtolower((string) $record->status) === 'absent'
        )->count();
        $totalRecords = $attendanceRecords->count();
        $attendanceRate = $totalRecords > 0 ? round(($totalAttended / $totalRecords) * 100, 1) : 0;

        return view('frontend.student.attendance', compact('student', 'attendanceRecords', 'totalAttended', 'totalAbsent', 'attendanceRate'));
    }

    public function attendanceSummary()
    {
        $user = Auth::user();
        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'student_id' => Student::generateStudentId(),
                'status' => 'active',
            ]
        );
        $records = AttendanceRecord::query()
            ->where(function ($query) use ($student): void {
                $query->where('student_id', $student->id)
                    ->orWhere('student_id_number', $student->student_id);
            })
            ->whereHas('session', fn ($query) => $query->where('status', 'closed'))
            ->get(['status', 'present', 'time_in']);

        $totalAttended = $records->filter(fn (AttendanceRecord $record): bool => (bool) $record->present
            || in_array(strtolower((string) $record->status), ['present', 'late'], true)
        )->count();
        $totalAbsent = $records->filter(fn (AttendanceRecord $record): bool => ! $record->present
            && strtolower((string) $record->status) === 'absent'
        )->count();
        $totalRecords = $records->count();

        return response()->json([
            'success' => true,
            'attended' => $totalAttended,
            'absent' => $totalAbsent,
            'rate' => $totalRecords > 0 ? round(($totalAttended / $totalRecords) * 100, 1) : 0,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Show student profile.
     */
    public function profile()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $student = Student::where('user_id', $user->id)->first();

        return view('frontend.student.profile', compact('student'));
    }
}
