<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function cancelledFixture(string $now): array
{
    Carbon::setTestNow($now);

    $faculty = User::factory()->create(['role' => 'faculty', 'department' => 'Information Technology']);
    $room = Classroom::query()->create(['name' => 'Room C', 'building' => 'Building A', 'capacity' => 40, 'status' => 'available']);
    $course = Course::query()->create([
        'code' => 'IT501',
        'title' => 'Cancel Test Class',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $room->id,
        'capacity' => 40,
    ]);

    return [$faculty, $room, $course];
}

function weeklySchedule(Course $course, Classroom $room, string $start, string $end, string $status = 'scheduled'): Schedule
{
    return Schedule::query()->create([
        'classroom_id' => $room->id,
        'course_id' => $course->id,
        'start_at' => $start,
        'end_at' => $end,
        'status' => $status,
        'day_of_week' => Carbon::parse($start)->dayOfWeek,
        'enrolled' => 20,
    ]);
}

it('keeps a cancelled class cancelled on the attendance screen until its time is over', function () {
    [$faculty, $room, $course] = cancelledFixture('2026-04-10 09:00:00');   // Friday
    weeklySchedule($course, $room, '2026-04-13 10:00:00', '2026-04-13 12:00:00', 'cancelled');
    weeklySchedule($course, $room, '2026-04-20 10:00:00', '2026-04-20 12:00:00');

    // Before the cancelled class: it is shown as Cancelled, not as Upcoming, and cannot be started.
    actingAs($faculty)->get(route('faculty.attendance'))
        ->assertOk()
        ->assertSee('data-status="cancelled"', false)
        ->assertDontSee('data-status="upcoming"', false)
        ->assertSee('This class was cancelled', false)
        ->assertSee('Mon, Apr 13');

    // During the cancelled class's time it is still shown as cancelled.
    Carbon::setTestNow('2026-04-13 10:30:00');
    actingAs($faculty)->get(route('faculty.attendance'))
        ->assertOk()
        ->assertSee('data-status="cancelled"', false);

    // After it is over the card points to the next real class and says which date that is.
    Carbon::setTestNow('2026-04-13 13:00:00');
    actingAs($faculty)->get(route('faculty.attendance'))
        ->assertOk()
        ->assertDontSee('data-status="cancelled"', false)
        ->assertSee('data-status="upcoming"', false)
        ->assertSee('Mon, Apr 20');
});

it('refuses to start attendance for a cancelled class', function () {
    [$faculty, $room, $course] = cancelledFixture('2026-04-10 09:00:00');
    $cancelled = weeklySchedule($course, $room, '2026-04-13 10:00:00', '2026-04-13 12:00:00', 'cancelled');

    actingAs($faculty)->postJson(route('faculty.attendance.quick.start'), ['schedule_id' => $cancelled->id])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('shows a cancelled class as cancelled on the student schedule', function () {
    [, $room, $course] = cancelledFixture('2026-04-10 09:00:00');
    weeklySchedule($course, $room, '2026-04-13 10:00:00', '2026-04-13 12:00:00', 'cancelled');

    $studentUser = User::factory()->create(['role' => 'student']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'student_id' => 'STU-CAN-1',
        'status' => 'active',
    ]);
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    actingAs($studentUser)->get(route('student.schedule'))
        ->assertOk()
        ->assertSee('is-cancelled', false)
        ->assertSee('Cancelled</span>', false);
});

it('does not count a cancelled class as one of the students classes today', function () {
    [, $room, $course] = cancelledFixture('2026-04-13 08:00:00');
    weeklySchedule($course, $room, '2026-04-13 10:00:00', '2026-04-13 12:00:00', 'cancelled');
    weeklySchedule($course, $room, '2026-04-13 13:00:00', '2026-04-13 15:00:00');

    $studentUser = User::factory()->create(['role' => 'student']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'student_id' => 'STU-CAN-2',
        'status' => 'active',
    ]);
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    actingAs($studentUser)->get(route('student.home'))
        ->assertOk()
        ->assertSee('id="today-classes-count">1</div>', false);
});

