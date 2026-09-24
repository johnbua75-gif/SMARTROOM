<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('does not count official class schedules as my reservations on the faculty dashboard', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'name' => 'Faculty User',
        'email' => 'faculty.dashboard@example.com',
    ]);

    $classroom = Classroom::query()->create([
        'name' => 'Room 15',
        'building' => 'Building A',
        'floor' => '1st Floor',
        'capacity' => 30,
        'status' => 'available',
    ]);

    $course = Course::query()->create([
        'code' => 'CS101',
        'title' => 'Intro to Programming',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $classroom->id,
        'capacity' => 30,
    ]);

    Schedule::query()->create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => Carbon::now()->startOfWeek()->addDays(1)->setTime(9, 0),
        'end_at' => Carbon::now()->startOfWeek()->addDays(1)->setTime(11, 0),
        'status' => 'scheduled',
        'enrolled' => 25,
    ]);

    $response = actingAs($faculty)
        ->get('/faculty_dashboard');

    $response->assertOk();

    expect($response->getContent())
        ->toMatch('/<div class="stat-card blue">.*?<div class="stat-value">0<\/div>.*?<div class="stat-label">My Reservations<\/div>/s');
});
