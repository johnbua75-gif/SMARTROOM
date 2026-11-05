<?php

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function instructorAttendanceUser(string $name, string $email, string $role): User
{
    return User::query()->create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make('Password123!'),
        'role' => $role,
        'status' => 'active',
        'must_change_password' => false,
    ]);
}

function instructorAttendanceSchedule(User $instructor, Classroom $classroom, string $code, string $date): Schedule
{
    $course = Course::query()->create([
        'code' => $code,
        'title' => $code.' Course',
        'instructor_user_id' => $instructor->id,
        'capacity' => 30,
    ]);

    return Schedule::query()->create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'instructor_user_id' => $instructor->id,
        'start_at' => $date.' 09:00:00',
        'end_at' => $date.' 10:00:00',
        'status' => 'scheduled',
        'day_of_week' => Carbon::parse($date)->dayOfWeek,
        'enrolled' => 20,
    ]);
}

beforeEach(function (): void {
    Carbon::setTestNow('2026-11-04 10:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('shows admin weekly instructor schedules and linked attendance sessions', function () {
    $admin = instructorAttendanceUser('Report Admin', 'report-admin@example.com', 'admin');
    $faculty = instructorAttendanceUser('Alex Faculty', 'alex-faculty@example.com', 'faculty');
    $classroom = Classroom::query()->create([
        'name' => 'Room 15',
        'building' => 'Main',
        'capacity' => 30,
        'status' => 'available',
    ]);
    $schedule = instructorAttendanceSchedule($faculty, $classroom, 'IT101', '2026-11-02');
    instructorAttendanceSchedule($faculty, $classroom, 'IT102', '2026-11-03');

    AttendanceSession::query()->create([
        'schedule_id' => $schedule->id,
        'course_id' => $schedule->course_id,
        'classroom_id' => $classroom->id,
        'room' => $classroom->name,
        'date' => '2026-11-02',
        'started_at' => '09:03:00',
        'ended_at' => '10:00:00',
        'status' => 'closed',
        'created_by' => $faculty->id,
    ]);

    actingAs($admin)
        ->get(route('admin.instructor-attendance', ['week' => '2026-11-04']))
        ->assertOk()
        ->assertSee('Faculty Reports')
        ->assertSee('Alex Faculty')
        ->assertSee('IT101')
        ->assertSee('href="'.route('admin.instructor-attendance').'"', false)
        ->assertSee('1 session logged')
        ->assertSee('No session logged')
        ->assertSee('Download CSV')
        ->assertSee('2', false);

    $html = $this->get(route('admin.instructor-attendance'))->getContent();
    expect(strpos($html, '<span>Announcements</span></a>'))
        ->toBeLessThan(strpos($html, '<span>Faculty Reports</span></a>'));
});

it('matches a session to its scheduled class when their dates differ', function () {
    $admin = instructorAttendanceUser('Date Match Admin', 'date-match-admin@example.com', 'admin');
    $faculty = instructorAttendanceUser('Date Match Faculty', 'date-match-faculty@example.com', 'faculty');
    $classroom = Classroom::query()->create([
        'name' => 'Room 21',
        'building' => 'Main',
        'capacity' => 30,
        'status' => 'available',
    ]);
    $schedule = instructorAttendanceSchedule($faculty, $classroom, 'IT301', '2026-11-02');

    AttendanceSession::query()->create([
        'schedule_id' => $schedule->id,
        'course_id' => $schedule->course_id,
        'classroom_id' => $classroom->id,
        'date' => '2026-11-03',
        'started_at' => '09:03:00',
        'status' => 'open',
        'created_by' => $faculty->id,
    ]);

    $response = actingAs($admin)->get(route('admin.instructor-attendance', ['week' => '2026-11-04']));
    $html = $response->assertOk()->getContent();

    expect(substr_count($html, 'IT301 - IT301 Course'))->toBe(1)
        ->and($html)->toContain('Classes with a session log')
        ->toContain('1 session logged')
        ->toContain('Started 9:03 AM');
});

it('filters instructor attendance and exports the selected week as csv', function () {
    $admin = instructorAttendanceUser('CSV Admin', 'csv-admin@example.com', 'admin');
    $faculty = instructorAttendanceUser('Selected Faculty', 'selected-faculty@example.com', 'faculty');
    $otherFaculty = instructorAttendanceUser('Other Faculty', 'other-faculty@example.com', 'faculty');
    $classroom = Classroom::query()->create([
        'name' => 'Room 20',
        'building' => 'Main',
        'capacity' => 30,
        'status' => 'available',
    ]);
    $selectedSchedule = instructorAttendanceSchedule($faculty, $classroom, 'IT201', '2026-11-03');
    instructorAttendanceSchedule($otherFaculty, $classroom, 'IT202', '2026-11-03');

    AttendanceSession::query()->create([
        'schedule_id' => $selectedSchedule->id,
        'course_id' => $selectedSchedule->course_id,
        'classroom_id' => $classroom->id,
        'date' => '2026-11-03',
        'started_at' => '09:00:00',
        'status' => 'open',
        'created_by' => $faculty->id,
    ]);

    $response = actingAs($admin)->get(route('admin.instructor-attendance.export', [
        'week' => '2026-11-04',
        'instructor_id' => $faculty->id,
    ]));

    $response->assertDownload('instructor-attendance-20261102.csv');
    expect($response->streamedContent())
        ->toContain('Selected Faculty')
        ->toContain('IT201')
        ->not->toContain('Other Faculty')
        ->not->toContain('IT202');
});

it('does not expose the campus-wide instructor report to faculty', function () {
    $faculty = instructorAttendanceUser('Private Faculty', 'private-faculty@example.com', 'faculty');

    actingAs($faculty)
        ->get(route('admin.instructor-attendance'))
        ->assertForbidden();
});
