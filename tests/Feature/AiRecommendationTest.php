<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

function createAiFacultyUser(): User {
    return User::create([
        'name' => 'AI Faculty',
        'email' => 'ai.faculty@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'department' => 'Information Technology',
        'must_change_password' => false,
    ]);
}

it('returns recommendations and excludes busy classrooms', function () {
    Carbon::setTestNow('2026-04-11 08:00:00');

    $faculty = createAiFacultyUser();

    // Busy classroom (has schedule overlapping next 2 hours)
    $busy = Classroom::create(['name' => 'Busy R', 'building' => 'A', 'capacity' => 30]);
    $course = Course::create(['code' => 'AI101', 'title' => 'AI', 'description' => 'AI', 'instructor_user_id' => $faculty->id, 'capacity' => 30]);
    Schedule::create([
        'classroom_id' => $busy->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-11 08:30:00',
        'end_at' => '2026-04-11 10:30:00',
        'status' => 'scheduled',
        'day_of_week' => 0,
        'enrolled' => 10,
    ]);

    // Free classrooms
    $free1 = Classroom::create(['name' => 'Free 1', 'building' => 'B', 'capacity' => 20]);
    $free2 = Classroom::create(['name' => 'Free 2', 'building' => 'B', 'capacity' => 25]);

    actingAs($faculty);

    $response = getJson('/api/ai/recommendations?hours=2');

    $response->assertOk()->assertJsonPath('success', true);

    $recs = collect($response->json('recommendations'));

    // Busy room should not be recommended
    expect($recs->pluck('id')->contains($busy->id))->toBeFalse();
    expect($recs->pluck('id')->contains($free1->id) || $recs->pluck('id')->contains($free2->id))->toBeTrue();
});

it('returns each room capacity and the actual time until its next booking', function () {
    Carbon::setTestNow('2026-04-11 08:00:00');

    $faculty = createAiFacultyUser();
    $room = Classroom::create([
        'name' => 'Live Room',
        'building' => 'A',
        'capacity' => 42,
    ]);
    $nextRoom = Classroom::create([
        'name' => 'Later Room',
        'building' => 'A',
        'capacity' => 28,
    ]);

    Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-04-11 13:30:00',
        'end_at' => '2026-04-11 14:30:00',
        'status' => 'reserved',
    ]);

    Reservation::create([
        'classroom_id' => $nextRoom->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-04-11 11:00:00',
        'end_at' => '2026-04-11 12:00:00',
        'status' => 'reserved',
    ]);

    actingAs($faculty);

    $recommendations = collect(getJson('/api/ai/recommendations?hours=2')
        ->assertSuccessful()
        ->json('recommendations'))
        ->keyBy('id');

    expect($recommendations->get($room->id))
        ->capacity->toBe(42)
        ->free_for->toBe(5.5);
    expect($recommendations->get($nextRoom->id))
        ->capacity->toBe(28)
        ->free_for->toBe(3);
});
