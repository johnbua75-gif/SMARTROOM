<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('prevents changing a course instructor after schedules exist', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $originalFaculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $replacementFaculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $classroom = Classroom::query()->create([
        'name' => 'History Room',
        'building' => 'Building A',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $course = Course::query()->create([
        'code' => 'HISTORY-101',
        'title' => 'History Protection',
        'instructor_user_id' => $originalFaculty->id,
        'classroom_id' => $classroom->id,
        'capacity' => 40,
    ]);
    $schedule = Schedule::query()->create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-06 09:00:00',
        'end_at' => '2026-04-06 10:00:00',
        'status' => 'completed',
        'day_of_week' => 1,
        'enrolled' => 20,
    ]);

    actingAs($admin)
        ->put(route('admin.courses.update', $course), [
            'instructor_user_id' => $replacementFaculty->id,
        ])
        ->assertSessionHasErrors('instructor_user_id');

    actingAs($admin)
        ->post(route('admin.schedule.store'), [
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'instructor_user_id' => $replacementFaculty->id,
            'semester_start' => '2026-04-08',
            'semester_end' => '2026-04-08',
            'day1' => 3,
            'day1_start' => '13:00',
            'day1_end' => '14:00',
            'day2' => 5,
            'day2_start' => '15:00',
            'day2_end' => '16:00',
        ])
        ->assertSessionHasErrors('instructor_user_id');

    expect($course->refresh()->instructor_user_id)->toBe($originalFaculty->id)
        ->and($schedule->refresh()->course->instructor_user_id)->toBe($originalFaculty->id)
        ->and(Schedule::query()->where('course_id', $course->id)->count())->toBe(1);
});

it('archives courses with schedules and enrollments without deleting their history', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $studentUser = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $student = Student::query()->create([
        'user_id' => $studentUser->id,
        'student_id' => 'ARCHIVE-STUDENT-01',
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'status' => 'active',
    ]);
    $faculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $classroom = Classroom::query()->create([
        'name' => 'Archive Room',
        'building' => 'Building B',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $course = Course::query()->create([
        'code' => 'ARCHIVE-101',
        'title' => 'Archived Course',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $classroom->id,
        'capacity' => 40,
    ]);
    $schedule = Schedule::query()->create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-06 09:00:00',
        'end_at' => '2026-04-06 10:00:00',
        'status' => 'completed',
        'day_of_week' => 1,
        'enrolled' => 1,
    ]);
    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'completed',
        'enrolled_at' => '2026-04-06 09:00:00',
    ]);

    actingAs($admin)
        ->delete(route('admin.courses.destroy', $course))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(Course::query()->whereKey($course->id)->exists())->toBeFalse()
        ->and(Course::withTrashed()->findOrFail($course->id)->trashed())->toBeTrue()
        ->and(Schedule::query()->whereKey($schedule->id)->exists())->toBeTrue()
        ->and(Enrollment::query()->whereKey($enrollment->id)->exists())->toBeTrue()
        ->and(Schedule::query()->with('course')->findOrFail($schedule->id)->course->title)->toBe('Archived Course')
        ->and(Enrollment::query()->with('course')->findOrFail($enrollment->id)->course->title)->toBe('Archived Course');
});

it('archives courses with history when deleted through the API', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $faculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $course = Course::query()->create([
        'code' => 'API-ARCHIVE-101',
        'title' => 'API Archived Course',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);
    Schedule::query()->create([
        'classroom_id' => Classroom::query()->create([
            'name' => 'API Archive Room',
            'building' => 'Building C',
            'capacity' => 30,
            'status' => 'available',
        ])->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-06 09:00:00',
        'end_at' => '2026-04-06 10:00:00',
        'status' => 'completed',
        'day_of_week' => 1,
        'enrolled' => 0,
    ]);

    actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/courses/'.$course->id)
        ->assertSuccessful()
        ->assertJsonPath('message', 'Course archived successfully because it has schedule or enrollment history.');

    expect(Course::query()->whereKey($course->id)->exists())->toBeFalse()
        ->and(Course::withTrashed()->whereKey($course->id)->exists())->toBeTrue()
        ->and(Schedule::query()->where('course_id', $course->id)->exists())->toBeTrue();
});

it('rejects force deleting a course that has schedule or enrollment history', function () {
    $faculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $classroom = Classroom::query()->create([
        'name' => 'Force Delete Room',
        'building' => 'Building D',
        'capacity' => 30,
        'status' => 'available',
    ]);
    $course = Course::query()->create([
        'code' => 'FORCE-DELETE-101',
        'title' => 'Force Delete Protection',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);
    $schedule = Schedule::query()->create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-06 09:00:00',
        'end_at' => '2026-04-06 10:00:00',
        'status' => 'completed',
        'day_of_week' => 1,
        'enrolled' => 0,
    ]);

    expect(fn () => $course->forceDelete())->toThrow(ValidationException::class);
    expect(Schedule::query()->whereKey($schedule->id)->exists())->toBeTrue()
        ->and(Course::query()->whereKey($course->id)->exists())->toBeTrue();
});

it('blocks faculty reassignment through the faculty-removal flow when course history exists', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $faculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $replacement = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $classroom = Classroom::query()->create([
        'name' => 'Faculty Removal Room',
        'building' => 'Building E',
        'capacity' => 30,
        'status' => 'available',
    ]);
    $course = Course::query()->create([
        'code' => 'FACULTY-REMOVAL-101',
        'title' => 'Faculty Removal History',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);
    Schedule::query()->create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-06 09:00:00',
        'end_at' => '2026-04-06 10:00:00',
        'status' => 'completed',
        'day_of_week' => 1,
        'enrolled' => 0,
    ]);

    actingAs($admin)
        ->deleteJson(route('admin.users.destroy.reassign', $faculty), [
            'replacement_user_id' => $replacement->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('replacement_user_id');

    expect($course->refresh()->instructor_user_id)->toBe($faculty->id);
});

it('blocks deleting faculty while assigned courses have history', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $faculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $course = Course::query()->create([
        'code' => 'FACULTY-DELETE-101',
        'title' => 'Faculty Deletion History',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);
    $classroom = Classroom::query()->create([
        'name' => 'Faculty Delete Room',
        'building' => 'Building F',
        'capacity' => 30,
        'status' => 'available',
    ]);
    Schedule::query()->create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-06 09:00:00',
        'end_at' => '2026-04-06 10:00:00',
        'status' => 'completed',
        'day_of_week' => 1,
        'enrolled' => 0,
    ]);

    actingAs($admin)
        ->deleteJson(route('admin.users.destroy', $faculty))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This faculty account cannot be deleted while assigned subjects have schedule or enrollment history.');

    expect(User::query()->whereKey($faculty->id)->exists())->toBeTrue()
        ->and($course->refresh()->instructor_user_id)->toBe($faculty->id);
});
