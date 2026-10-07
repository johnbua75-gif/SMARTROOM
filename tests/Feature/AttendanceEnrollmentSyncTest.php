<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Notification;
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

it('does not expose attendance records without student identifiers to a student missing their profile', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $studentUser = User::factory()->create(['role' => 'student']);
    $session = AttendanceSession::create([
        'date' => today()->toDateString(),
        'status' => 'closed',
        'created_by' => $faculty->id,
        'token' => 'ORPHANED-ATTENDANCE-TEST',
    ]);

    AttendanceRecord::create([
        'attendance_session_id' => $session->id,
        'student_name' => 'Unlinked Student Attendance',
        'status' => 'absent',
        'present' => false,
    ]);

    expect(Student::query()->where('user_id', $studentUser->id)->exists())->toBeFalse();

    actingAs($studentUser)
        ->getJson('/student/attendance/summary')
        ->assertSuccessful()
        ->assertJsonPath('attended', 0)
        ->assertJsonPath('absent', 0)
        ->assertJsonPath('rate', 0);

    Student::query()->where('user_id', $studentUser->id)->delete();

    actingAs($studentUser)
        ->get('/student/attendance')
        ->assertSuccessful()
        ->assertDontSee('Unlinked Student Attendance')
        ->assertViewHas('attendanceRecords', fn ($records) => $records->isEmpty());

    expect(Student::query()->where('user_id', $studentUser->id)->exists())->toBeTrue();
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

it('requires admin assignment for course enrollment and prevents students from changing it', function () {
    $studentUser = User::factory()->create(['role' => 'student']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'ASSIGNMENT-TEST-01',
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'status' => 'active',
    ]);
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $assignedCourse = Course::create(['code' => 'ASSIGN101', 'title' => 'Assigned Course']);
    $unassignedCourse = Course::create(['code' => 'UNASSIGN101', 'title' => 'Unassigned Course']);
    $enrollment = Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $assignedCourse->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    actingAs($studentUser)
        ->get('/student/courses')
        ->assertSuccessful()
        ->assertSee('An instructor must be assigned before you can request this course.')
        ->assertDontSee('Enroll in course')
        ->assertDontSee('Remove course');

    $this->post('/student/courses/'.$unassignedCourse->id.'/enroll')->assertNotFound();
    $this->delete('/student/courses/'.$assignedCourse->id.'/enroll')->assertNotFound();

    $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'active']);
    $this->assertDatabaseMissing('enrollments', [
        'student_id' => $student->id,
        'course_id' => $unassignedCourse->id,
    ]);

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/enrollments', [
            'student_id' => $student->id,
            'course_id' => $unassignedCourse->id,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $student->id,
        'course_id' => $unassignedCourse->id,
        'status' => 'active',
    ]);
});

it('notifies the assigned faculty and activates enrollment only after approval', function () {
    $studentUser = User::factory()->create(['role' => 'student']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'REQUEST-STUDENT-01',
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'status' => 'active',
    ]);
    $faculty = User::factory()->create(['role' => 'faculty']);
    $otherFaculty = User::factory()->create(['role' => 'faculty']);
    $course = Course::create([
        'code' => 'REQUEST101',
        'title' => 'Enrollment Request Course',
        'instructor_user_id' => $faculty->id,
    ]);

    actingAs($studentUser)
        ->post('/student/courses/'.$course->id.'/request')
        ->assertRedirect(route('student.courses'));

    $enrollment = Enrollment::query()
        ->where('student_id', $student->id)
        ->where('course_id', $course->id)
        ->firstOrFail();
    $notification = Notification::query()
        ->where('user_id', $faculty->id)
        ->where('type', 'enrollment_request')
        ->firstOrFail();

    expect($enrollment->status)->toBe('pending')
        ->and($notification->data)->toMatchArray([
            'enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_id_number' => $student->student_id,
            'course_id' => $course->id,
        ]);

    $this->assertDatabaseMissing('notifications', [
        'user_id' => $otherFaculty->id,
        'type' => 'enrollment_request',
    ]);

    actingAs($otherFaculty)
        ->post('/faculty/enrollment-requests/'.$enrollment->id.'/approve')
        ->assertNotFound();

    expect($enrollment->fresh()->status)->toBe('pending');

    actingAs($faculty)
        ->get('/faculty-notifications')
        ->assertSuccessful()
        ->assertSee($student->name)
        ->assertSee($student->student_id)
        ->assertSee($course->code);

    $this->post('/faculty/enrollment-requests/'.$enrollment->id.'/approve')
        ->assertRedirect(route('faculty.notifications'));

    expect($enrollment->fresh()->status)->toBe('active');
    $this->assertDatabaseHas('notifications', [
        'user_id' => $studentUser->id,
        'type' => 'enrollment_status',
    ]);

    actingAs($studentUser)
        ->get('/student/courses/'.$course->id)
        ->assertSuccessful();
});

it('allows students to resubmit an enrollment request after faculty rejects it', function () {
    $studentUser = User::factory()->create(['role' => 'student']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'REQUEST-STUDENT-02',
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'status' => 'active',
    ]);
    $faculty = User::factory()->create(['role' => 'faculty']);
    $course = Course::create([
        'code' => 'REQUEST102',
        'title' => 'Resubmittable Course',
        'instructor_user_id' => $faculty->id,
    ]);

    actingAs($studentUser)
        ->post('/student/courses/'.$course->id.'/request')
        ->assertRedirect(route('student.courses'));

    $enrollment = Enrollment::query()
        ->where('student_id', $student->id)
        ->where('course_id', $course->id)
        ->firstOrFail();

    actingAs($faculty)
        ->post('/faculty/enrollment-requests/'.$enrollment->id.'/reject')
        ->assertRedirect(route('faculty.notifications'));

    expect($enrollment->fresh()->status)->toBe('rejected');

    actingAs($studentUser)
        ->get('/student/courses')
        ->assertSuccessful()
        ->assertSee('Request declined')
        ->assertSee('Request enrollment');

    $this->post('/student/courses/'.$course->id.'/request')
        ->assertRedirect(route('student.courses'));

    expect($enrollment->fresh()->status)->toBe('pending')
        ->and(Notification::query()->where('user_id', $faculty->id)->where('type', 'enrollment_request')->count())->toBe(2);
});

it('does not let students reopen inactive or suspended enrollments', function () {
    $studentUser = User::factory()->create(['role' => 'student']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'RESTRICTED-STUDENT-01',
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'status' => 'active',
    ]);
    $faculty = User::factory()->create(['role' => 'faculty']);
    $restrictedEnrollments = collect(['inactive', 'suspended'])->mapWithKeys(function (string $status, int $index) use ($faculty, $student): array {
        $course = Course::create([
            'code' => 'RESTRICTED'.$index,
            'title' => 'Restricted '.$status.' Course',
            'instructor_user_id' => $faculty->id,
        ]);
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => $status,
        ]);

        return [$course->id => ['course' => $course, 'enrollment' => $enrollment, 'status' => $status]];
    });

    actingAs($studentUser)
        ->get('/student/courses')
        ->assertSuccessful()
        ->assertSee('Enrollment is managed by your administrator.')
        ->assertDontSee('Request enrollment');

    foreach ($restrictedEnrollments as $restricted) {
        $this->post('/student/courses/'.$restricted['course']->id.'/request')
            ->assertRedirect(route('student.courses'))
            ->assertSessionHas('error');

        expect($restricted['enrollment']->fresh()->status)->toBe($restricted['status']);
    }

    expect(Notification::query()->where('type', 'enrollment_request')->count())->toBe(0);
});

it('redirects the legacy student schedule route to the supported schedule page', function () {
    $studentUser = User::factory()->create(['role' => 'student']);

    actingAs($studentUser)
        ->get('/student/student-schedule')
        ->assertRedirect(route('student.schedule'));
});

it('blocks QR check-in for an inactive student profile even if an enrollment is active', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $studentUser = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'INACTIVE-PROFILE-01',
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'status' => 'inactive',
    ]);
    $course = Course::create([
        'code' => 'INACTIVE101',
        'title' => 'Inactive Profile Course',
        'instructor_user_id' => $faculty->id,
    ]);
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);
    $session = AttendanceSession::create([
        'course_id' => $course->id,
        'date' => today()->toDateString(),
        'started_at' => now()->format('H:i:s'),
        'status' => 'open',
        'created_by' => $faculty->id,
        'token' => 'INACTIVE-PROFILE-SESSION',
    ]);

    actingAs($studentUser)
        ->postJson('/attendance/checkin/'.$session->token)
        ->assertForbidden()
        ->assertJsonPath('message', 'Student profile is inactive');

    $this->assertDatabaseMissing('attendance_records', [
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
    ]);
});
