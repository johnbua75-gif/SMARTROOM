<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\AttendanceRecord;
use App\Services\RoomAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Show the student home dashboard.
     */
    public function home(RoomAvailabilityService $availabilityService)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('auth.login');
        }

        // Check or create student record
        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'student_id' => 'STU-' . rand(10000, 99999),
                'status' => 'active',
            ]
        );

        $enrolledCourseIds = $student->courses()->pluck('courses.id');

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
            fn ($schedule) => $schedule->start_at instanceof \Carbon\CarbonInterface
                && $schedule->start_at->greaterThan(now())
        );

        $nextClassTime = $nextClass
            ? \Carbon\Carbon::parse($nextClass->start_at)->format('g:i A')
            : 'N/A';

        $classrooms = Classroom::query()->get();
        $availableRoomsCount = $availabilityService
            ->buildRoomStatuses($classrooms, now(), now()->copy()->addHour())
            ->where('status', 'available')
            ->count();

        // Pass data to view
        return view('frontend.student.home', compact('student', 'todaySchedules', 'todayClassesCount', 'nextClassTime', 'availableRoomsCount'));
    }

    public function homeSummary(RoomAvailabilityService $availabilityService): JsonResponse
    {
        $student = Student::query()->where('user_id', Auth::id())->firstOrFail();
        $enrolledCourseIds = $student->courses()->pluck('courses.id');
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

        return response()->json([
            'success' => true,
            'today_classes' => $todaySchedules->count(),
            'next_class' => $nextClass?->start_at?->format('g:i A') ?? 'N/A',
            'available_rooms' => $availableRoomsCount,
            'attended' => $attended,
            'attendance_rate' => $total > 0 ? round(($attended / $total) * 100, 1) : 0,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Show checking room details.
     */
    public function checkingRoom()
    {
        $user = Auth::user();

        if (!$user) {
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
                'student_id' => 'STU-'.rand(10000, 99999),
                'status' => 'active',
            ]
        );

        $courses = Course::query()
            ->with('instructor')
            ->orderBy('code')
            ->orderBy('title')
            ->get();

        $enrolledCourseIds = $student->courses()->pluck('courses.id')->all();

        return view('frontend.student.courses', compact('student', 'courses', 'enrolledCourseIds'));
    }

    public function enrolledCourses(): View
    {
        $student = Student::query()->where('user_id', Auth::id())->firstOrFail();
        $courses = $student->courses()->with('instructor')->orderBy('code')->orderBy('title')->get();

        return view('frontend.student.enrolled-courses', compact('student', 'courses'));
    }

    public function courseOverview(Course $course): View
    {
        $student = Student::query()->where('user_id', Auth::id())->firstOrFail();
        abort_unless($student->courses()->whereKey($course->id)->exists(), 404);

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

    public function enrollCourse(Course $course): RedirectResponse
    {
        $user = Auth::user();

        $student = Student::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'email' => $user->email,
                'student_id' => 'STU-'.rand(10000, 99999),
                'status' => 'active',
            ]
        );

        Enrollment::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'course_id' => $course->id,
            ],
            [
                'enrolled_at' => now(),
                'status' => 'active',
            ]
        );

        return to_route('student.courses')->with('success', 'Course enrolled successfully.');
    }

    public function unenrollCourse(Course $course): RedirectResponse
    {
        $student = Student::query()->where('user_id', Auth::id())->firstOrFail();

        Enrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->delete();

        return to_route('student.courses')->with('success', 'Course removed from your schedule.');
    }

    /**
     * Show student schedule.
     */
    public function schedule()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('auth.login');
        }

        $student = Student::where('user_id', $user->id)->first();

        // Fetch only schedules for courses the student is enrolled in
        $enrolledCourseIds = $student?->courses()->pluck('courses.id') ?? collect();
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
    public function studentSchedule()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $student = Student::where('user_id', $user->id)->first();

        // Fetch only schedules for courses the student is enrolled in
        $enrolledCourseIds = $student?->courses()->pluck('courses.id') ?? collect();
        $hasEnrolledCourses = $enrolledCourseIds->isNotEmpty();

        $schedules = $hasEnrolledCourses
            ? Schedule::whereIn('course_id', $enrolledCourseIds)
                ->whereNotNull('start_at')
                ->with(['course', 'classroom'])
                ->orderBy('start_at')
                ->get()
            : collect([]);

        return view('frontend.student.studentSchedule', compact('student', 'schedules', 'hasEnrolledCourses'));
    }

    /**
     * Show attendance page.
     */
    public function attendance()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('auth.login');
        }

        $student = Student::where('user_id', $user->id)->first();

                // Fetch attendance records (match by student PK or student id number)
                $attendanceRecords = \App\Models\AttendanceRecord::with('session.course')->where(function($q) use ($student) {
                        $q->where('student_id', optional($student)->id)
                            ->orWhere('student_id_number', optional($student)->student_id);
                        })->whereHas('session', fn ($query) => $query->where('status', 'closed'))
                            ->orderBy('created_at', 'desc')->get();
        
        // Calculate stats
            $totalAttended = $attendanceRecords->whereNotNull('time_in')->count();
            $totalAbsent = $attendanceRecords->filter(fn ($record): bool =>
                is_null($record->time_in) && strtolower((string) $record->status) === 'absent'
            )->count();
        $totalRecords = $attendanceRecords->count();
        $attendanceRate = $totalRecords > 0 ? round(($totalAttended / $totalRecords) * 100, 1) : 0;

        return view('frontend.student.attendance', compact('student', 'attendanceRecords', 'totalAttended', 'totalAbsent', 'attendanceRate'));
    }

    public function attendanceSummary()
    {
        $student = Student::query()->where('user_id', Auth::id())->firstOrFail();
        $records = AttendanceRecord::query()
            ->where(function ($query) use ($student): void {
                $query->where('student_id', $student->id)
                    ->orWhere('student_id_number', $student->student_id);
            })
            ->whereHas('session', fn ($query) => $query->where('status', 'closed'))
            ->get(['status', 'present', 'time_in']);

        $totalAttended = $records->whereNotNull('time_in')->count();
        $totalAbsent = $records->filter(fn ($record): bool =>
            is_null($record->time_in) && $record->status === 'absent'
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

        if (!$user) {
            return redirect()->route('auth.login');
        }

        $student = Student::where('user_id', $user->id)->first();

        return view('frontend.student.profile', compact('student'));
    }
}
