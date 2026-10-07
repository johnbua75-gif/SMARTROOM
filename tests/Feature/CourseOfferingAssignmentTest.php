<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('assigns separate sections of one subject to different faculty without changing the catalog assignment', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $catalogInstructor = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $blockAInstructor = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $blockBInstructor = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $roomA = Classroom::query()->create([
        'name' => 'Offering Room A',
        'building' => 'Building A',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $roomB = Classroom::query()->create([
        'name' => 'Offering Room B',
        'building' => 'Building B',
        'capacity' => 40,
        'status' => 'available',
    ]);
    $course = Course::query()->create([
        'code' => 'OFFERING-101',
        'title' => 'Offering Model Test',
        'instructor_user_id' => $catalogInstructor->id,
        'capacity' => 40,
    ]);

    actingAs($admin)
        ->post(route('admin.schedule.store'), [
            'classroom_id' => $roomA->id,
            'course_id' => $course->id,
            'instructor_user_id' => $blockAInstructor->id,
            'block_section' => 'Block A',
            'semester_start' => '2026-04-06',
            'semester_end' => '2026-04-10',
            'day1' => 1,
            'day1_start' => '09:00',
            'day1_end' => '10:00',
            'day2' => 3,
            'day2_start' => '09:00',
            'day2_end' => '10:00',
        ])
        ->assertRedirect(route('admin.schedule'));

    actingAs($admin)
        ->post(route('admin.schedule.store'), [
            'classroom_id' => $roomB->id,
            'course_id' => $course->id,
            'instructor_user_id' => $blockBInstructor->id,
            'block_section' => 'Block B',
            'semester_start' => '2026-04-06',
            'semester_end' => '2026-04-10',
            'day1' => 1,
            'day1_start' => '09:00',
            'day1_end' => '10:00',
            'day2' => 3,
            'day2_start' => '09:00',
            'day2_end' => '10:00',
        ])
        ->assertRedirect(route('admin.schedule'));

    $offerings = CourseOffering::query()->where('course_id', $course->id)->get()->keyBy('block_section');

    expect($course->fresh()->instructor_user_id)->toBe($catalogInstructor->id)
        ->and($offerings)->toHaveCount(2)
        ->and($offerings['Block A']->instructor_user_id)->toBe($blockAInstructor->id)
        ->and($offerings['Block B']->instructor_user_id)->toBe($blockBInstructor->id)
        ->and(Schedule::query()->forInstructor($blockAInstructor->id)->pluck('course_offering_id')->unique()->all())->toBe([$offerings['Block A']->id])
        ->and(Schedule::query()->forInstructor($blockBInstructor->id)->pluck('course_offering_id')->unique()->all())->toBe([$offerings['Block B']->id])
        ->and(Notification::query()->where('user_id', $blockAInstructor->id)->where('type', 'course_assignment')->where('data->course_offering_id', $offerings['Block A']->id)->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $blockBInstructor->id)->where('type', 'course_assignment')->where('data->course_offering_id', $offerings['Block B']->id)->exists())->toBeTrue();
});

it('routes student enrollment requests to the selected offering instructor', function () {
    $catalogInstructor = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $sectionInstructor = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $studentUser = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $student = Student::query()->create([
        'user_id' => $studentUser->id,
        'student_id' => 'OFFERING-STUDENT-01',
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'status' => 'active',
    ]);
    $course = Course::query()->create([
        'code' => 'OFFERING-ENROLL-101',
        'title' => 'Section Enrollment Test',
        'instructor_user_id' => $catalogInstructor->id,
        'capacity' => 40,
    ]);
    $offering = CourseOffering::query()->create([
        'course_id' => $course->id,
        'instructor_user_id' => $sectionInstructor->id,
        'block_section' => 'Block B',
        'term_start' => '2026-04-06',
        'term_end' => '2026-04-10',
    ]);

    actingAs($studentUser)
        ->post(route('student.courses.request', $course), [
            'course_offering_id' => $offering->id,
        ])
        ->assertRedirect(route('student.courses'));

    $enrollment = Enrollment::query()->where('student_id', $student->id)->firstOrFail();

    expect($enrollment->course_id)->toBe($course->id)
        ->and($enrollment->course_offering_id)->toBe($offering->id)
        ->and(Notification::query()->where('type', 'enrollment_request')->where('user_id', $sectionInstructor->id)->where('data->enrollment_id', $enrollment->id)->exists())->toBeTrue()
        ->and(Notification::query()->where('type', 'enrollment_request')->where('user_id', $catalogInstructor->id)->exists())->toBeFalse();
});
