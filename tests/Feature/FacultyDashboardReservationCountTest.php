<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

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
        ->toMatch('/<div class="stat-card blue">.*?<span class="stat-title">My Reservations<\/span>.*?<div class="stat-value">0<\/div>/s');
});

it('keeps the faculty dashboard query count low by avoiding full schedule hydration', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'name' => 'Faculty User',
        'email' => 'faculty.dashboard.query@example.com',
    ]);

    $classroom = Classroom::query()->create([
        'name' => 'Room 20',
        'building' => 'Building A',
        'floor' => '2nd Floor',
        'capacity' => 40,
        'status' => 'available',
    ]);

    $course = Course::query()->create([
        'code' => 'CS201',
        'title' => 'Algorithms',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $classroom->id,
        'capacity' => 40,
    ]);

    for ($i = 0; $i < 12; $i++) {
        Schedule::query()->create([
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'start_at' => Carbon::now()->addDays($i + 1)->setTime(9, 0),
            'end_at' => Carbon::now()->addDays($i + 1)->setTime(10, 30),
            'status' => 'scheduled',
            'enrolled' => 20 + $i,
        ]);
    }

    DB::enableQueryLog();

    $response = actingAs($faculty)
        ->get('/faculty_dashboard');

    $response->assertOk();

    $queryCount = count(DB::getQueryLog());

    expect($queryCount)->toBeLessThan(10);
});

it('keeps the faculty reports page efficient by avoiding full schedule in-memory scans', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'name' => 'Faculty Reports',
        'email' => 'faculty.reports@example.com',
    ]);

    $classroom = Classroom::query()->create([
        'name' => 'Room 30',
        'building' => 'Building B',
        'floor' => '3rd Floor',
        'capacity' => 50,
        'status' => 'available',
    ]);

    $course = Course::query()->create([
        'code' => 'CS301',
        'title' => 'Operating Systems',
        'instructor_user_id' => $faculty->id,
        'classroom_id' => $classroom->id,
        'capacity' => 50,
    ]);

    foreach ([1, 2, 3, 4, 5, 6] as $dayOffset) {
        Schedule::query()->create([
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'start_at' => Carbon::now()->startOfWeek()->addDays($dayOffset)->setTime(9, 0),
            'end_at' => Carbon::now()->startOfWeek()->addDays($dayOffset)->setTime(10, 30),
            'status' => 'scheduled',
            'enrolled' => 30 + $dayOffset,
        ]);
    }

    DB::enableQueryLog();

    $response = actingAs($faculty)
        ->get('/reports');

    $response->assertOk();

    $queryCount = count(DB::getQueryLog());

    expect($queryCount)->toBeLessThan(8);
});
