<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminDataController;
use App\Http\Controllers\AiRecommendationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReservationController as ApiReservationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\FacultyScheduleAssistantController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('frontend.landing');
});

Route::get('/landing', function () {
    return view('frontend.landing');
});

Route::get('/login', [PageController::class, 'login'])->name('auth.login');

Route::middleware('guest')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login.submit');
    Route::get('/signup', [PageController::class, 'signup'])->name('auth.signup');
    Route::post('/signup', [AuthController::class, 'signup'])->name('auth.signup.submit');
    Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'updatePassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/password/change', [AuthController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.change.submit');
});

Route::middleware(['auth', 'password.changed', 'role:student'])->group(function (): void {
    Route::get('/student/home', [StudentController::class, 'home'])->name('student.home');
    Route::get('/student/home/summary', [StudentController::class, 'homeSummary'])->name('student.home.summary');
    Route::get('/student/checkingRoom', [StudentController::class, 'checkingRoom'])->name('student.checkingRoom');
    Route::get('/student/courses', [StudentController::class, 'courses'])->name('student.courses');
    Route::get('/student/courses/enrolled', [StudentController::class, 'enrolledCourses'])->name('student.courses.enrolled');
    Route::get('/student/courses/{course}', [StudentController::class, 'courseOverview'])->name('student.courses.overview');
    Route::post('/student/courses/{course}/enroll', [StudentController::class, 'enrollCourse'])->name('student.courses.enroll');
    Route::delete('/student/courses/{course}/enroll', [StudentController::class, 'unenrollCourse'])->name('student.courses.unenroll');
    Route::get('/student/schedule', [StudentController::class, 'schedule'])->name('student.schedule');
    Route::get('/student/student-schedule', [StudentController::class, 'studentSchedule'])->name('student.studentSchedule');
    Route::get('/student/attendance', [StudentController::class, 'attendance'])->name('student.attendance');
    Route::get('/student/attendance/summary', [StudentController::class, 'attendanceSummary'])->name('student.attendance.summary');
    Route::get('/student/profile', [StudentController::class, 'profile'])->name('student.profile');
});

Route::middleware(['auth', 'password.changed'])->group(function (): void {
    Route::post('/attendance/checkin/{token}', [AttendanceController::class, 'studentCheckin'])->name('attendance.checkin');
    Route::get('/attendance/checkin/{token}', [AttendanceController::class, 'showCheckin'])->name('attendance.checkin.show');
});

Route::middleware(['auth', 'password.changed', 'role:faculty'])->group(function (): void {
    Route::get('/faculty_dashboard', [FacultyController::class, 'dashboard'])->name('faculty.dashboard');

    Route::get('/rooms', [FacultyController::class, 'rooms'])->name('faculty.rooms');
    Route::get('/rooms/export/csv', [FacultyController::class, 'exportRoomsCsv'])->name('faculty.rooms.export.csv');
    Route::get('/rfid-verification', [FacultyController::class, 'rfidVerification'])->name('faculty.rfid.verification');
    Route::get('/faculty-notifications', [FacultyController::class, 'notifications'])->name('faculty.notifications');
    Route::get('/faculty-notifications/data', [NotificationController::class, 'index'])->name('faculty.notifications.data');

    Route::get('/faculty-schedule', [ScheduleController::class, 'facultyIndex'])->name('faculty.schedule');
    Route::get('/faculty-schedule/export/ics', [ScheduleController::class, 'exportFacultyIcs'])->name('faculty.schedule.export.ics');
    Route::post('/faculty-schedule', [ScheduleController::class, 'facultyStore'])->name('faculty.schedule.store');
    Route::patch('/faculty-schedule/{schedule}/cancel', [ScheduleController::class, 'facultyCancel'])->name('faculty.schedule.cancel');

    Route::get('/faculty_schedule', [ScheduleController::class, 'facultyIndex']);

    Route::get('/ai-recommendations', function () {
        return view('frontend.faculty.ai-recommendations');
    });

    // Faculty Attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('faculty.attendance');
    Route::get('/attendance/dashboard', [AttendanceController::class, 'dashboard'])->name('faculty.attendance.dashboard');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('faculty.attendance.store');
    // Quick attendance: Get instructor's courses and auto-start sessions
    Route::get('/attendance/quick/courses', [AttendanceController::class, 'getInstructorCourses'])->name('faculty.attendance.quick.courses');
    Route::post('/attendance/quick/start', [AttendanceController::class, 'quickAttendanceStart'])->name('faculty.attendance.quick.start');
    Route::get('/attendance/live', [AttendanceController::class, 'liveOverview'])->name('faculty.attendance.live');
    Route::get('/attendance/{id}/students', [AttendanceController::class, 'studentRoster'])->name('faculty.attendance.students');
    Route::get('/attendance/{id}/status', [AttendanceController::class, 'sessionStatus'])->name('faculty.attendance.status');
    // Card reader webhook to start session
    Route::post('/attendance/start-hook', [AttendanceController::class, 'startFromCard'])->name('faculty.attendance.start-hook');
    Route::get('/attendance/{id}/qr', [AttendanceController::class, 'showQr'])->name('faculty.attendance.qr');
    Route::get('/attendance/{id}', [AttendanceController::class, 'showSession'])->name('faculty.attendance.session');
    Route::get('/attendance/{id}/export', [AttendanceController::class, 'export'])->name('faculty.attendance.export');

    // AI recommendations (simple heuristic endpoint)
    Route::get('/api/ai/recommendations', [AiRecommendationController::class, 'index']);
    Route::post('/attendance/{id}/records', [AttendanceController::class, 'storeRecord'])->name('faculty.attendance.record');
    Route::post('/attendance/{id}/records/bulk', [AttendanceController::class, 'storeRecordsBulk'])->name('faculty.attendance.record.bulk');
    Route::post('/attendance/{id}/close', [AttendanceController::class, 'close'])->name('faculty.attendance.close');

    Route::get('/reports', [FacultyController::class, 'reports'])->name('faculty.reports');
    Route::get('/reports/export/csv', [FacultyController::class, 'exportReportsCsv'])->name('faculty.reports.export.csv');

    Route::post('/api/faculty/schedule-assistant', [FacultyScheduleAssistantController::class, 'ask'])
        ->name('faculty.schedule.assistant.ask');

    Route::prefix('api/v1')->group(function (): void {
        Route::middleware('role:faculty')->group(function (): void {
            Route::get('/reservations/mine', [FacultyController::class, 'myReservations'])->name('faculty.reservations.mine');
            Route::post('/reservations', [ApiReservationController::class, 'store']);
            Route::patch('/reservations/{reservation}', [ApiReservationController::class, 'update']);
            Route::delete('/reservations/{reservation}', [ApiReservationController::class, 'destroy']);
        });
    });
});

Route::middleware(['auth', 'password.changed', 'role:admin'])->group(function (): void {
    Route::get('/dashboard', [AdminController::class, 'users'])->name('dashboard');
    Route::get('/admin/dashboard', [AdminController::class, 'users'])->name('admin.dashboard');

    Route::get('/admin/classrooms', [ClassroomController::class, 'index'])->name('admin.classrooms');
    Route::get('/admin/classrooms/{id}', [ClassroomController::class, 'show'])->name('admin.classrooms.show');
    Route::post('/admin/classrooms/{classroom}/occupancy', [ClassroomController::class, 'updateOccupancy'])->name('admin.classrooms.occupancy');
    Route::get('/admin/classrooms/{classroom}/qr', [ClassroomController::class, 'qr'])->name('admin.classrooms.qr');
    Route::post('/admin/classrooms', [ClassroomController::class, 'store'])->name('admin.classrooms.store');
    Route::match(['put', 'patch'], '/admin/classrooms/{classroom}', [ClassroomController::class, 'update'])->name('admin.classrooms.update');
    Route::delete('/admin/classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('admin.classrooms.destroy');

    Route::get('/admin/schedule', [ScheduleController::class, 'index'])->name('admin.schedule');
    Route::get('/admin/schedule/export/csv', [ScheduleController::class, 'exportCsv'])->name('admin.schedule.export.csv');
    Route::get('/admin/schedule/subjects', [ScheduleController::class, 'subjectsByYearLevel'])->name('admin.schedule.subjects');
    Route::delete('/admin/schedule/bulk-delete', [ScheduleController::class, 'bulkDestroy'])->name('admin.schedule.bulk-destroy');
    Route::get('/admin/schedule/{id}', [ScheduleController::class, 'show'])->name('admin.schedule.show');
    Route::post('/admin/schedule', [ScheduleController::class, 'store'])->name('admin.schedule.store');
    Route::post('/admin/schedule/import/preview', [ScheduleController::class, 'importPreview'])->name('admin.schedule.import.preview');
    Route::post('/admin/schedule/import', [ScheduleController::class, 'importStore'])->name('admin.schedule.import.store');
    Route::match(['put', 'patch'], '/admin/schedule/{schedule}', [ScheduleController::class, 'update'])->name('admin.schedule.update');
    Route::delete('/admin/schedule/{schedule}', [ScheduleController::class, 'destroy'])->name('admin.schedule.destroy');

    Route::post('/admin/courses', [AdminDataController::class, 'storeCourse'])->name('admin.courses.store');
    Route::match(['put', 'patch'], '/admin/courses/{course}', [AdminDataController::class, 'updateCourse'])->name('admin.courses.update');
    Route::patch('/admin/courses/{course}/unassign', [AdminDataController::class, 'unassignCourse'])->name('admin.courses.unassign');
    Route::delete('/admin/courses/{course}', [AdminDataController::class, 'destroyCourse'])->name('admin.courses.destroy');

    Route::post('/admin/access-cards', [AdminDataController::class, 'storeAccessCard'])->name('admin.access-cards.store');
    Route::match(['put', 'patch'], '/admin/access-cards/{accessCard}', [AdminDataController::class, 'updateAccessCard'])->name('admin.access-cards.update');
    Route::delete('/admin/access-cards/{accessCard}', [AdminDataController::class, 'destroyAccessCard'])->name('admin.access-cards.destroy');

    Route::post('/admin/access-logs', [AdminDataController::class, 'storeAccessLog'])->name('admin.access-logs.store');
    Route::match(['put', 'patch'], '/admin/access-logs/{accessLog}', [AdminDataController::class, 'updateAccessLog'])->name('admin.access-logs.update');
    Route::delete('/admin/access-logs/{accessLog}', [AdminDataController::class, 'destroyAccessLog'])->name('admin.access-logs.destroy');

    Route::get('/smartlocking', [AdminController::class, 'smartlocking'])->name('smartlocking.index');
    Route::get('/smartlocking/{id}', [AdminController::class, 'smartlockingDetail'])->name('smartlocking.show');

    // Admin SmartLocking route for tab navigation
    Route::get('/admin/smartlocking', [AdminController::class, 'smartlocking'])->name('admin.smartlocking');

    // Access Logs screen
    Route::get('/admin/accessLogs', [AdminController::class, 'accessLogs'])->name('accessLogs');
    Route::get('/admin/accessLogs/export/{format}', [AdminController::class, 'exportAccessLogs'])->name('admin.accessLogs.export');
    Route::get('/admin/accessLogs/export/csv', [AdminController::class, 'exportAccessLogsCsv'])->name('admin.accessLogs.export.csv');

    // Admin Reports
    Route::get('/admin/reports', function () {
        return view('frontend.admin.reports');
    })->name('admin.reports');

    // Admin User Management
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/users/data', [AdminController::class, 'usersData'])->name('admin.users.data');
    Route::post('/admin/users', [AdminDataController::class, 'storeUser'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
    Route::match(['put', 'patch'], '/admin/users/{user}', [AdminDataController::class, 'updateUser'])->name('admin.users.update');
    Route::post('/admin/users/{user}/reset-password', [AdminDataController::class, 'resetUserPassword'])->name('admin.users.reset-password');
    Route::delete('/admin/users/{user}', [AdminDataController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::delete('/admin/users/{user}/reassign', [AdminDataController::class, 'destroyUserWithReassign'])->name('admin.users.destroy.reassign');

});
