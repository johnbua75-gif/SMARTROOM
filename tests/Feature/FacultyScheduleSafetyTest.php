<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('blocks a faculty schedule that overlaps another class taught by the same instructor', function () {
    Carbon::setTestNow('2026-04-06 08:00:00');

    $faculty = User::factory()->create([
        'role' => 'faculty',
        'department' => 'Information Technology',
    ]);
    $existingRoom = Classroom::query()->create([
        'name' => 'Room Existing',
        'building' => 'Building A',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $newRoom = Classroom::query()->create([
        'name' => 'Room New',
        'building' => 'Building B',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $existingCourse = Course::query()->create([
        'code' => 'IT101',
        'title' => 'Existing Class',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $existingRoom->id,
        'capacity' => 40,
    ]);
    $newCourse = Course::query()->create([
        'code' => 'IT102',
        'title' => 'New Class',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $newRoom->id,
        'capacity' => 40,
    ]);

    Schedule::query()->create([
        'classroom_id' => $existingRoom->id,
        'course_id' => $existingCourse->id,
        'start_at' => '2026-04-06 09:00:00',
        'end_at' => '2026-04-06 11:00:00',
        'status' => 'scheduled',
        'day_of_week' => 1,
        'enrolled' => 20,
    ]);

    $response = actingAs($faculty)->post(route('faculty.schedule.store'), [
        'course_id' => $newCourse->id,
        'block_section' => 'Block B',
        'semester_start' => '2026-04-06',
        'semester_end' => '2026-04-10',
        'day1' => 1,
        'day1_start' => '10:00',
        'day1_end' => '12:00',
        'enrolled' => 20,
    ]);

    $response->assertSessionHasErrors('day1_start');
    expect(Schedule::query()->where('course_id', $newCourse->id)->count())->toBe(0);
});

it('stores the selected block on a faculty schedule occurrence', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'department' => 'Information Technology',
    ]);
    $classroom = Classroom::query()->create([
        'name' => 'Room Block',
        'building' => 'Building A',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $course = Course::query()->create([
        'code' => 'IT201',
        'title' => 'Block Test Class',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $classroom->id,
        'capacity' => 40,
    ]);

    actingAs($faculty)->post(route('faculty.schedule.store'), [
        'course_id' => $course->id,
        'block_section' => 'Block A',
        'semester_start' => '2026-04-06',
        'semester_end' => '2026-04-06',
        'day1' => 1,
        'day1_start' => '09:00',
        'day1_end' => '10:00',
        'enrolled' => 20,
    ])->assertRedirect(route('faculty.schedule'));

    expect(Schedule::query()->where('course_id', $course->id)->value('block_section'))->toBe('Block A');
});

it('cancels one class occurrence with a reason and releases its room slot', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'department' => 'Information Technology',
    ]);
    $classroom = Classroom::query()->create([
        'name' => 'Room Emergency',
        'building' => 'Building A',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $course = Course::query()->create([
        'code' => 'IT301',
        'title' => 'Emergency Cancellation Class',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $classroom->id,
        'capacity' => 40,
    ]);
    $schedule = Schedule::query()->create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-06 09:00:00',
        'end_at' => '2026-04-06 11:00:00',
        'status' => 'scheduled',
        'day_of_week' => 1,
        'enrolled' => 20,
    ]);

    actingAs($faculty)
        ->patch(route('faculty.schedule.cancel', $schedule), [
            'cancellation_reason' => 'Emergency faculty absence',
        ])
        ->assertRedirect(route('faculty.schedule'))
        ->assertSessionHas('status');

    expect($schedule->refresh()->status)->toBe('cancelled')
        ->and($schedule->cancellation_reason)->toBe('Emergency faculty absence');
});

// ── Faculty: move a single class occurrence ──────────────────────────────────

function movableClassFixture(array $overrides = []): array
{
    Carbon::setTestNow('2026-04-06 08:00:00');

    $faculty = User::factory()->create(['role' => 'faculty', 'department' => 'Information Technology']);
    $roomA = Classroom::query()->create(['name' => 'Room A', 'building' => 'Building A', 'capacity' => 40, 'status' => 'available']);
    $roomB = Classroom::query()->create(['name' => 'Room B', 'building' => 'Building A', 'capacity' => 40, 'status' => 'available']);
    $course = Course::query()->create([
        'code' => 'IT401',
        'title' => 'Movable Class',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $roomA->id,
        'capacity' => 40,
    ]);
    $schedule = Schedule::query()->create(array_merge([
        'classroom_id' => $roomA->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-08 09:00:00',
        'end_at' => '2026-04-08 11:00:00',
        'status' => 'scheduled',
        'day_of_week' => 3,
        'enrolled' => 20,
    ], $overrides));

    return [$faculty, $roomA, $roomB, $course, $schedule];
}

function moveClass(User $faculty, Schedule $schedule, array $data)
{
    return actingAs($faculty)->patch(route('faculty.schedule.update', $schedule), $data);
}

it('lets an instructor move one class to a new date, time and room', function () {
    [$faculty, $roomA, $roomB, $course, $schedule] = movableClassFixture();
    $sibling = Schedule::query()->create([
        'classroom_id' => $roomA->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-15 09:00:00',
        'end_at' => '2026-04-15 11:00:00',
        'status' => 'scheduled',
        'day_of_week' => 3,
        'enrolled' => 20,
    ]);

    moveClass($faculty, $schedule, [
        'date' => '2026-04-09',
        'start_time' => '13:00',
        'end_time' => '15:00',
        'classroom_id' => $roomB->id,
    ])->assertRedirect(route('faculty.schedule'))->assertSessionHas('status');

    $schedule->refresh();
    expect($schedule->start_at->format('Y-m-d H:i'))->toBe('2026-04-09 13:00')
        ->and($schedule->end_at->format('Y-m-d H:i'))->toBe('2026-04-09 15:00')
        ->and($schedule->classroom_id)->toBe($roomB->id)
        ->and($schedule->day_of_week)->toBe(4);

    // Only this occurrence moved: the other week's class is untouched.
    expect($sibling->refresh()->start_at->format('Y-m-d H:i'))->toBe('2026-04-15 09:00')
        ->and($sibling->classroom_id)->toBe($roomA->id);
});

it('does not let an instructor edit another instructors class', function () {
    [, , $roomB, , $schedule] = movableClassFixture();
    $otherFaculty = User::factory()->create(['role' => 'faculty', 'department' => 'Information Technology']);

    moveClass($otherFaculty, $schedule, [
        'date' => '2026-04-09',
        'start_time' => '13:00',
        'end_time' => '15:00',
        'classroom_id' => $roomB->id,
    ])->assertNotFound();

    expect($schedule->refresh()->start_at->format('Y-m-d H:i'))->toBe('2026-04-08 09:00');
});

it('blocks moving a class into a room that another class already occupies', function () {
    [$faculty, , $roomB, , $schedule] = movableClassFixture();
    $otherFaculty = User::factory()->create(['role' => 'faculty', 'department' => 'Information Technology']);
    $otherCourse = Course::query()->create([
        'code' => 'IT402',
        'title' => 'Other Class',
        'instructor_user_id' => $otherFaculty->id,
        'classroom_id' => $roomB->id,
        'capacity' => 40,
    ]);
    Schedule::query()->create([
        'classroom_id' => $roomB->id,
        'course_id' => $otherCourse->id,
        'start_at' => '2026-04-09 13:00:00',
        'end_at' => '2026-04-09 15:00:00',
        'status' => 'scheduled',
        'day_of_week' => 4,
        'enrolled' => 10,
    ]);

    moveClass($faculty, $schedule, [
        'date' => '2026-04-09',
        'start_time' => '14:00',
        'end_time' => '16:00',
        'classroom_id' => $roomB->id,
    ])->assertSessionHasErrors('classroom_id');

    expect($schedule->refresh()->classroom_id)->not->toBe($roomB->id);
});

it('blocks moving a class onto another class the same instructor teaches', function () {
    [$faculty, $roomA, $roomB, , $schedule] = movableClassFixture();
    $secondCourse = Course::query()->create([
        'code' => 'IT403',
        'title' => 'Second Class',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $roomA->id,
        'capacity' => 40,
    ]);
    Schedule::query()->create([
        'classroom_id' => $roomA->id,
        'course_id' => $secondCourse->id,
        'start_at' => '2026-04-09 13:00:00',
        'end_at' => '2026-04-09 15:00:00',
        'status' => 'scheduled',
        'day_of_week' => 4,
        'enrolled' => 10,
    ]);

    moveClass($faculty, $schedule, [
        'date' => '2026-04-09',
        'start_time' => '14:00',
        'end_time' => '16:00',
        'classroom_id' => $roomB->id,
    ])->assertSessionHasErrors('start_time');

    expect($schedule->refresh()->start_at->format('Y-m-d H:i'))->toBe('2026-04-08 09:00');
});

it('rejects moves outside operating hours, into the past, or with an end before the start', function () {
    [$faculty, , $roomB, , $schedule] = movableClassFixture();

    moveClass($faculty, $schedule, [
        'date' => '2026-04-09', 'start_time' => '17:00', 'end_time' => '18:30', 'classroom_id' => $roomB->id,
    ])->assertSessionHasErrors('start_time');

    moveClass($faculty, $schedule, [
        'date' => '2026-04-01', 'start_time' => '09:00', 'end_time' => '10:00', 'classroom_id' => $roomB->id,
    ])->assertSessionHasErrors('date');

    moveClass($faculty, $schedule, [
        'date' => '2026-04-09', 'start_time' => '10:00', 'end_time' => '09:00', 'classroom_id' => $roomB->id,
    ])->assertSessionHasErrors('end_time');

    expect($schedule->refresh()->start_at->format('Y-m-d H:i'))->toBe('2026-04-08 09:00');
});

it('does not allow editing a cancelled class or one that already started', function () {
    [$faculty, , $roomB, , $cancelled] = movableClassFixture(['status' => 'cancelled', 'cancellation_reason' => 'Away']);

    moveClass($faculty, $cancelled, [
        'date' => '2026-04-09', 'start_time' => '13:00', 'end_time' => '15:00', 'classroom_id' => $roomB->id,
    ])->assertSessionHasErrors('schedule');

    $started = Schedule::query()->create([
        'classroom_id' => $roomB->id,
        'course_id' => $cancelled->course_id,
        'start_at' => '2026-04-06 07:00:00',
        'end_at' => '2026-04-06 09:00:00',
        'status' => 'scheduled',
        'day_of_week' => 1,
        'enrolled' => 20,
    ]);

    moveClass($faculty, $started, [
        'date' => '2026-04-09', 'start_time' => '13:00', 'end_time' => '15:00', 'classroom_id' => $roomB->id,
    ])->assertSessionHasErrors('schedule');

    expect($started->refresh()->start_at->format('Y-m-d H:i'))->toBe('2026-04-06 07:00');
});
