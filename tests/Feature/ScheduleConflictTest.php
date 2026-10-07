<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('rolls back subject assignments when an admin recurring schedule conflicts with a room schedule', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $originalFaculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $otherFaculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $oldRoom = Classroom::query()->create([
        'name' => 'Original Room',
        'building' => 'Building A',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $targetRoom = Classroom::query()->create([
        'name' => 'Target Room',
        'building' => 'Building A',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $course = Course::query()->create([
        'code' => 'ROLLBACK-101',
        'title' => 'Rollback Test',
        'instructor_user_id' => $originalFaculty->id,
        'classroom_id' => $oldRoom->id,
        'capacity' => 40,
    ]);
    $conflictingCourse = Course::query()->create([
        'code' => 'ROOM-CONFLICT-101',
        'title' => 'Room Conflict Test',
        'instructor_user_id' => $otherFaculty->id,
        'classroom_id' => $targetRoom->id,
        'capacity' => 40,
    ]);

    Schedule::query()->create([
        'classroom_id' => $targetRoom->id,
        'course_id' => $conflictingCourse->id,
        'start_at' => '2026-04-06 10:00:00',
        'end_at' => '2026-04-06 11:00:00',
        'status' => 'scheduled',
        'day_of_week' => 1,
        'enrolled' => 20,
    ]);

    actingAs($admin)
        ->post(route('admin.schedule.store'), [
            'classroom_id' => $targetRoom->id,
            'course_id' => $course->id,
            'instructor_user_id' => $otherFaculty->id,
            'semester_start' => '2026-04-06',
            'semester_end' => '2026-04-06',
            'day1' => 1,
            'day1_start' => '10:00',
            'day1_end' => '11:00',
            'day2' => 3,
            'day2_start' => '13:00',
            'day2_end' => '14:00',
        ])
        ->assertSessionHasErrors('classroom_id');

    expect($course->refresh()->instructor_user_id)->toBe($originalFaculty->id)
        ->and($course->classroom_id)->toBe($oldRoom->id)
        ->and(Schedule::query()->where('course_id', $course->id)->exists())->toBeFalse();
});

it('blocks admin recurring schedules that overlap the assigned faculty schedule in another room', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $faculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $originalFaculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $existingRoom = Classroom::query()->create([
        'name' => 'Faculty Existing Room',
        'building' => 'Building B',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $newRoom = Classroom::query()->create([
        'name' => 'Faculty New Room',
        'building' => 'Building C',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $existingCourse = Course::query()->create([
        'code' => 'FACULTY-OVERLAP-101',
        'title' => 'Existing Faculty Class',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $existingRoom->id,
        'capacity' => 40,
    ]);
    $course = Course::query()->create([
        'code' => 'FACULTY-OVERLAP-102',
        'title' => 'New Faculty Class',
        'instructor_user_id' => $originalFaculty->id,
        'classroom_id' => $existingRoom->id,
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

    actingAs($admin)
        ->post(route('admin.schedule.store'), [
            'classroom_id' => $newRoom->id,
            'course_id' => $course->id,
            'instructor_user_id' => $faculty->id,
            'semester_start' => '2026-04-06',
            'semester_end' => '2026-04-06',
            'day1' => 1,
            'day1_start' => '10:00',
            'day1_end' => '12:00',
            'day2' => 3,
            'day2_start' => '13:00',
            'day2_end' => '14:00',
        ])
        ->assertSessionHasErrors('instructor_user_id');

    expect($course->refresh()->instructor_user_id)->toBe($originalFaculty->id)
        ->and($course->classroom_id)->toBe($existingRoom->id)
        ->and(Schedule::query()->where('course_id', $course->id)->exists())->toBeFalse();
});

it('blocks API schedule creation when the assigned faculty member is already teaching', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $faculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $existingRoom = Classroom::query()->create([
        'name' => 'API Existing Room',
        'building' => 'Building D',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $newRoom = Classroom::query()->create([
        'name' => 'API New Room',
        'building' => 'Building E',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $existingCourse = Course::query()->create([
        'code' => 'API-OVERLAP-101',
        'title' => 'Existing API Class',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $existingRoom->id,
        'capacity' => 40,
    ]);
    $newCourse = Course::query()->create([
        'code' => 'API-OVERLAP-102',
        'title' => 'New API Class',
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

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/schedules', [
            'classroom_id' => $newRoom->id,
            'course_id' => $newCourse->id,
            'start_at' => '2026-04-06 10:00:00',
            'end_at' => '2026-04-06 12:00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('course_id');

    expect(Schedule::query()->where('course_id', $newCourse->id)->exists())->toBeFalse();
});
