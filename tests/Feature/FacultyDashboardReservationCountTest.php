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

it('shows the next non-cancelled faculty class with its time and room', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-06 09:00:00'));

    $faculty = User::factory()->create([
        'role' => 'faculty',
        'name' => 'Faculty Schedule User',
        'email' => 'faculty.next-class@example.com',
    ]);
    $room = Classroom::query()->create([
        'name' => 'Room 15',
        'building' => 'Main Building',
        'status' => 'available',
    ]);
    $course = Course::query()->create([
        'code' => 'CS101',
        'title' => 'Introduction to Programming',
        'instructor_user_id' => $faculty->id,
    ]);

    Schedule::query()->create([
        'classroom_id' => $room->id,
        'course_id' => $course->id,
        'start_at' => Carbon::now()->copy()->addMinutes(15),
        'end_at' => Carbon::now()->copy()->addMinutes(75),
        'status' => 'cancelled',
    ]);
    Schedule::query()->create([
        'classroom_id' => $room->id,
        'course_id' => $course->id,
        'start_at' => Carbon::now()->copy()->addMinutes(30),
        'end_at' => Carbon::now()->copy()->addMinutes(90),
        'status' => 'scheduled',
    ]);

    $response = actingAs($faculty)->get('/faculty_dashboard');
    Carbon::setTestNow();

    $response->assertOk()
        ->assertSee('Today / Next Class')
        ->assertSee('Introduction to Programming')
        ->assertSee('9:30 AM - 10:30 AM')
        ->assertSee('Room 15')
        ->assertDontSee('No upcoming classes')
        ->assertDontSee('+3 new');
});

it('shows a useful empty state when the faculty has no upcoming classes', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'name' => 'Faculty Without Classes',
        'email' => 'faculty.empty-schedule@example.com',
    ]);

    actingAs($faculty)
        ->get('/faculty_dashboard')
        ->assertOk()
        ->assertSee('No upcoming classes')
        ->assertSee('Your next class will appear here.')
        ->assertDontSee('+3 new');
});

it('shows a currently ongoing class as in progress', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-06 09:00:00'));

    $faculty = User::factory()->create([
        'role' => 'faculty',
        'name' => 'Faculty Teaching Now',
        'email' => 'faculty.ongoing-class@example.com',
    ]);
    $room = Classroom::query()->create([
        'name' => 'Room 20',
        'building' => 'Main Building',
        'status' => 'available',
    ]);
    $course = Course::query()->create([
        'code' => 'CS201',
        'title' => 'Current Class',
        'instructor_user_id' => $faculty->id,
    ]);
    Schedule::query()->create([
        'classroom_id' => $room->id,
        'course_id' => $course->id,
        'start_at' => Carbon::now()->copy()->subMinutes(30),
        'end_at' => Carbon::now()->copy()->addMinutes(30),
        'status' => 'ongoing',
    ]);

    $response = actingAs($faculty)->get('/faculty_dashboard');
    Carbon::setTestNow();

    $response->assertOk()
        ->assertSee('In progress')
        ->assertSee('Current Class')
        ->assertSee('8:30 AM - 9:30 AM');
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

it('reports faculty schedule counts and teaching metrics accurately', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'name' => 'Faculty Metrics',
        'email' => 'faculty.metrics@example.com',
    ]);

    $classroom = Classroom::query()->create([
        'name' => 'Room Metrics',
        'building' => 'Building C',
        'capacity' => 40,
        'status' => 'available',
    ]);

    $courseA = Course::query()->create([
        'code' => 'CS401',
        'title' => 'Course A',
        'instructor_user_id' => $faculty->id,
        'capacity' => 40,
    ]);
    $courseB = Course::query()->create([
        'code' => 'CS402',
        'title' => 'Course B',
        'instructor_user_id' => $faculty->id,
        'capacity' => 40,
    ]);

    $monday = Carbon::now()->startOfWeek();
    $schedules = [
        [$courseA, $monday->copy()->setTime(9, 0), $monday->copy()->setTime(10, 0), 'scheduled'],
        [$courseB, $monday->copy()->addDays(2)->setTime(10, 30), $monday->copy()->addDays(2)->setTime(12, 0), 'scheduled'],
        [$courseA, $monday->copy()->addDays(2)->setTime(13, 0), $monday->copy()->addDays(2)->setTime(14, 0), 'scheduled'],
        [$courseB, $monday->copy()->addDays(4)->setTime(15, 0), $monday->copy()->addDays(4)->setTime(16, 0), 'cancelled'],
    ];

    foreach ($schedules as [$course, $startAt, $endAt, $status]) {
        Schedule::query()->create([
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'instructor_user_id' => $faculty->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => $status,
            'enrolled' => 25,
        ]);
    }

    $response = actingAs($faculty)->get('/reports');

    $response->assertOk()
        ->assertViewHas('stats', function (array $stats): bool {
            return $stats[0]['label'] === 'Scheduled Classes'
                && $stats[0]['value'] === '3'
                && $stats[1]['label'] === 'Teaching Hours'
                && $stats[1]['value'] === '3.5 hrs'
                && $stats[2]['label'] === 'Courses Scheduled'
                && $stats[2]['value'] === '2'
                && $stats[3]['label'] === 'Cancellation Rate'
                && $stats[3]['value'] === '25.0%'
                && $stats[3]['change'] === '1 cancelled';
        })
        ->assertSee('Scheduled Classes by Day')
        ->assertSee('Class Starts by Time Block')
        ->assertSee('9–11 AM')
        ->assertSee('11 AM–1 PM')
        ->assertDontSee('Avg. Utilization')
        ->assertDontSee('Active Users')
        ->assertDontSee('Conflict Rate');

    expect($response->viewData('bar_data'))->toBe([1, 0, 2, 0, 0, 0])
        ->and($response->viewData('line_data'))->toBe([0, 2, 0, 1, 0]);
});
