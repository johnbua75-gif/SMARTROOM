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
