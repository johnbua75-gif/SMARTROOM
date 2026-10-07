<?php

use App\Models\Course;
use App\Models\Student;

it('renders course images based on each subject title', function () {
    $courses = collect([
        ['id' => 101, 'code' => 'A_CC 102', 'title' => 'Fundamental of Programming'],
        ['id' => 102, 'code' => 'A_MS 101', 'title' => 'Discrete Mathematics'],
        ['id' => 103, 'code' => 'A_GE 3', 'title' => 'Art Appreciation'],
        ['id' => 104, 'code' => 'A_PE 1', 'title' => 'PATH-FIT I'],
    ])->map(function (array $attributes): Course {
        $course = new Course($attributes);
        $course->setAttribute('id', $attributes['id']);
        $course->setRelation('instructor', null);

        return $course;
    });
    $student = new Student(['name' => 'Test Student', 'student_id' => 'STU-1000']);

    $html = view('frontend.student.courses', [
        'student' => $student,
        'studentId' => $student->student_id,
        'courses' => $courses,
        'enrolledCourseIds' => [],
        'enrollmentStatuses' => collect(),
    ])->render();

    expect(substr_count($html, 'class="course-card-image"'))->toBe(4)
        ->and($html)->toContain('images/courses/computing.jpg')
        ->and($html)->toContain('images/courses/mathematics.jpg')
        ->and($html)->toContain('images/courses/arts.jpg')
        ->and($html)->toContain('images/courses/sports.jpg')
        ->and($html)->not->toContain('.course-card::before');
});
