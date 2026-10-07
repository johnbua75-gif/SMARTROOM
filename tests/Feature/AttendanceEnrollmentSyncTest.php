<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('syncs active course enrollments into faculty attendance rosters', function () {
    $faculty = User::create([
        'name' => 'Faculty Tester',
        'email' => 'faculty-attendance@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'department' => 'Information Technology',
        'must_change_password' => false,
    ]);
    $studentUser = User::create([
        'name' => 'Student Tester',
        'email' => 'student-attendance@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'student',
        'must_change_password' => false,
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'student_id' => 'STU-10001',
        'status' => 'active',
    ]);
    $course = Course::create([
        'code' => 'SYNC101',
        'title' => 'Attendance Sync',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);
    $classroom = Classroom::create([
        'name' => 'Sync Room',
        'building' => 'Building A',
        'floor' => '1st Floor',
        'capacity' => 30,
    ]);
    $schedule = Schedule::create([
        'course_id' => $course->id,
        'classroom_id' => $classroom->id,
        'start_at' => now()->subMinutes(10),
        'end_at' => now()->addMinutes(50),
        'status' => 'ongoing',
        'day_of_week' => now()->dayOfWeek,
    ]);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    actingAs($faculty);

    $response = postJson('/attendance/quick/start', ['schedule_id' => $schedule->id]);

    $response->assertSuccessful();

    $sessionId = $response->json('session_id');

    expect(AttendanceRecord::query()
        ->where('attendance_session_id', $sessionId)
        ->where('student_id', $student->id)
        ->where('status', 'absent')
        ->exists())->toBeTrue();

    getJson('/attendance/live')
        ->assertSuccessful()
        ->assertJsonPath('sessions.0.checked_in', 0);

    getJson('/attendance/'.$sessionId.'/students')
        ->assertSuccessful()
        ->assertJsonPath('students.0.name', $student->name)
        ->assertJsonPath('students.0.status', 'absent')
        ->assertJsonPath('students.0.checked_in', false);

    postJson('/attendance/'.$sessionId.'/close')
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $newSessionResponse = postJson('/attendance/quick/start', ['schedule_id' => $schedule->id])
        ->assertSuccessful();
    $newSessionId = $newSessionResponse->json('session_id');

    expect($newSessionId)->not->toBe($sessionId)
        ->and(AttendanceSession::find($sessionId)->status)->toBe('closed')
        ->and(AttendanceRecord::query()->where('attendance_session_id', $sessionId)->count())->toBe(1)
        ->and(AttendanceRecord::query()->where('attendance_session_id', $newSessionId)->where('status', 'absent')->exists())->toBeTrue();
});

it('allows an enrolled student to check in through the QR route', function () {
    $faculty = User::create([
        'name' => 'QR Faculty',
        'email' => 'qr-faculty@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'must_change_password' => false,
    ]);
    $studentUser = User::create([
        'name' => 'QR Student',
        'email' => 'qr-student@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'student',
        'must_change_password' => false,
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'student_id' => 'STU-10002',
        'status' => 'active',
    ]);
    $course = Course::create([
        'code' => 'QR101',
        'title' => 'QR Attendance',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    $session = AttendanceSession::create([
        'course_id' => $course->id,
        'date' => now()->toDateString(),
        'started_at' => now()->format('H:i:s'),
        'status' => 'open',
        'created_by' => $faculty->id,
        'token' => 'QR-TEST-10002',
        'expires_at' => now()->addHour(),
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $session->id,
        'student_name' => $student->name,
        'student_id' => $student->id,
        'student_id_number' => $student->student_id,
        'status' => 'absent',
        'present' => false,
    ]);

    actingAs($studentUser);

    postJson('/attendance/checkin/'.$session->token)
        ->assertSuccessful()
        ->assertJsonPath('message', 'Checked in');

    expect(AttendanceRecord::query()
        ->where('attendance_session_id', $session->id)
        ->where('student_id', $student->id)
        ->where('status', 'present')
        ->where('present', true)
        ->exists())->toBeTrue();

    actingAs($faculty);

    getJson('/attendance/'.$session->id.'/status')
        ->assertSuccessful()
        ->assertJsonPath('checked_in', 1)
        ->assertJsonPath('present', 1);

    getJson('/attendance/live')
        ->assertSuccessful()
        ->assertJsonPath('open_sessions', 1)
        ->assertJsonPath('sessions.0.id', $session->id)
        ->assertJsonPath('sessions.0.checked_in', 1);

    postJson('/attendance/'.$session->id.'/close')
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    actingAs($studentUser);

    getJson('/student/attendance/summary')
        ->assertSuccessful()
        ->assertJsonPath('attended', 1)
        ->assertJsonPath('absent', 0)
        ->assertJsonPath('rate', 100);

    getJson('/student/home/summary')
        ->assertSuccessful()
        ->assertJsonPath('today_classes', 0)
        ->assertJsonPath('attended', 1)
        ->assertJsonPath('attendance_rate', 100);
});

it('counts manually marked present and late records without a check-in time', function () {
    $studentUser = User::create([
        'name' => 'Manual Attendance Student',
        'email' => 'manual-attendance-student@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'student',
        'must_change_password' => false,
    ]);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'student_id' => 'STU-10003',
        'status' => 'active',
    ]);
    $faculty = User::create([
        'name' => 'Manual Attendance Faculty',
        'email' => 'manual-attendance-faculty@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'must_change_password' => false,
    ]);
    $session = AttendanceSession::create([
        'date' => now()->toDateString(),
        'status' => 'closed',
        'created_by' => $faculty->id,
        'token' => 'MANUAL-ATTENDANCE-TEST',
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
        'student_id_number' => $student->student_id,
        'student_name' => $student->name,
        'status' => 'present',
        'present' => true,
        'time_in' => null,
    ]);
    AttendanceRecord::create([
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
        'student_id_number' => $student->student_id,
        'student_name' => $student->name,
        'status' => 'late',
        'present' => true,
        'time_in' => null,
    ]);
    AttendanceRecord::create([
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
        'student_id_number' => $student->student_id,
        'student_name' => $student->name,
        'status' => 'absent',
        'present' => false,
        'time_in' => null,
    ]);

    actingAs($studentUser);

    $this->get('/student/attendance')
        ->assertSuccessful()
        ->assertSee('My attendance')
        ->assertSee('Scan QR code')
        ->assertSee('Attendance history')
        ->assertSee('id="openQrScanner"', false)
        ->assertSee('id="student-attendance-rate"', false)
        ->assertSee('id="qr-reader"', false)
        ->assertViewHas('totalAttended', 2)
        ->assertViewHas('totalAbsent', 1)
        ->assertViewHas('attendanceRate', 66.7)
        ->assertSee('Late')
        ->assertSee('Present')
        ->assertSee('Absent');

    getJson('/student/attendance/summary')
        ->assertSuccessful()
        ->assertJsonPath('attended', 2)
        ->assertJsonPath('absent', 1)
        ->assertJsonPath('rate', 66.7);
});

it('restricts the student directory search to faculty accounts', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'email' => 'directory-target@psu.edu.ph',
    ]);
    $anotherStudent = User::factory()->create(['role' => 'student']);
    $faculty = User::factory()->create(['role' => 'faculty']);

    actingAs($anotherStudent);

    getJson('/api/students/search?email=DIRECTORY-TARGET')
        ->assertForbidden();

    actingAs($faculty);

    getJson('/api/students/search?email=DIRECTORY-TARGET')
        ->assertSuccessful()
        ->assertJsonPath('students.0.email', $student->email);
});
