<?php

/**
 * Reservation Flow Tests
 *
 * Tests the complete reservation lifecycle: create, update, cancel,
 * authorization, conflict detection, and ESP32 access check.
 */

use App\Models\AccessCard;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

// ── Helpers ──────────────────────────────────────────────────────

function makeFaculty(array $overrides = []): User
{
    return User::create(array_merge([
        'name' => 'Faculty User',
        'email' => 'faculty.'.uniqid().'@psu.edu',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'department' => 'Information Technology',
        'must_change_password' => false,
    ], $overrides));
}

function makeStudent(array $overrides = []): User
{
    return User::create(array_merge([
        'name' => 'Student User',
        'email' => 'student.'.uniqid().'@psu.edu',
        'password' => Hash::make('Password123!'),
        'role' => 'student',
        'department' => 'Information Technology',
        'must_change_password' => false,
    ], $overrides));
}

function makeRoom(array $overrides = []): Classroom
{
    return Classroom::create(array_merge([
        'name' => 'Room '.rand(100, 999),
        'building' => 'Building A',
        'floor' => '1st Floor',
        'capacity' => 40,
    ], $overrides));
}

// ── Store (Create Reservation) ──────────────────────────────────

it('creates a reservation successfully for a faculty user', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty = makeFaculty();
    $room = makeRoom();

    actingAs($faculty);

    $response = postJson('/api/v1/reservations', [
        'classroom_id' => $room->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'notes' => 'Faculty meeting',
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'Reservation created successfully.');

    expect(Reservation::count())->toBe(1);
    expect(Reservation::first()->status)->toBe('approved'); // auto-approved for faculty
    expect(Reservation::first()->notes)->toBe('Faculty meeting');
});

it('rejects reservation with missing required fields', function () {
    $faculty = makeFaculty();
    actingAs($faculty);

    postJson('/api/v1/reservations', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['classroom_id', 'start_at', 'end_at']);
});

it('rejects reservation where end_at is before start_at', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty = makeFaculty();
    $room = makeRoom();

    actingAs($faculty);

    postJson('/api/v1/reservations', [
        'classroom_id' => $room->id,
        'start_at' => '2026-06-15 11:00:00',
        'end_at' => '2026-06-15 09:00:00',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['end_at']);
});

it('rejects reservations that start in the past', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty = makeFaculty();
    $room = makeRoom();

    actingAs($faculty);

    postJson('/api/v1/reservations', [
        'classroom_id' => $room->id,
        'start_at' => '2026-06-14 09:00:00',
        'end_at' => '2026-06-14 10:00:00',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_at']);

    expect(Reservation::query()->exists())->toBeFalse();
});

it('rejects reservations for rooms marked unavailable', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty = makeFaculty();
    $room = makeRoom([
        'status' => 'maintenance',
        'unavailable_reason' => 'Air conditioning repair',
    ]);

    actingAs($faculty);

    postJson('/api/v1/reservations', [
        'classroom_id' => $room->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['classroom_id'])
        ->assertJsonPath('errors.classroom_id.0', 'Air conditioning repair');

    expect(Reservation::count())->toBe(0);
});

it('rejects reservation for a nonexistent classroom', function () {
    $faculty = makeFaculty();
    actingAs($faculty);

    postJson('/api/v1/reservations', [
        'classroom_id' => 99999,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['classroom_id']);
});

// ── Authorization ───────────────────────────────────────────────

it('blocks students from creating reservations', function () {
    $student = makeStudent();
    $room = makeRoom();

    actingAs($student);

    postJson('/api/v1/reservations', [
        'classroom_id' => $room->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
    ])->assertForbidden();

    expect(Reservation::count())->toBe(0);
});

it('blocks unauthenticated users from creating reservations', function () {
    $room = makeRoom();

    postJson('/api/v1/reservations', [
        'classroom_id' => $room->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
    ])->assertUnauthorized();
});

it('prevents a faculty from cancelling another faculty reservation', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty1 = makeFaculty(['name' => 'Faculty One']);
    $faculty2 = makeFaculty(['name' => 'Faculty Two']);
    $room = makeRoom();

    $reservation = Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty1->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'status' => 'approved',
    ]);

    actingAs($faculty2);

    deleteJson("/api/v1/reservations/{$reservation->id}")
        ->assertForbidden();

    expect($reservation->fresh()->status)->toBe('approved');
});

it('allows admins to approve a reservation', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $faculty = makeFaculty();
    $room = makeRoom();
    $reservation = Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'status' => 'reserved',
    ]);

    actingAs($admin);

    patchJson('/api/v1/reservations/'.$reservation->id, ['status' => 'approved'])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'approved');

    expect($reservation->fresh()->status)->toBe('approved');
});

it('does not allow faculty to approve a reservation through the update endpoint', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty = makeFaculty();
    $room = makeRoom();
    $reservation = Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'status' => 'reserved',
    ]);

    actingAs($faculty);

    patchJson('/api/v1/reservations/'.$reservation->id, ['status' => 'approved'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    expect($reservation->fresh()->status)->toBe('reserved');
});

// ── Conflict Detection ──────────────────────────────────────────

it('prevents double-booking the same room at the same time', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty1 = makeFaculty(['name' => 'Faculty A']);
    $faculty2 = makeFaculty(['name' => 'Faculty B']);
    $room = makeRoom();

    // First reservation succeeds
    actingAs($faculty1);
    postJson('/api/v1/reservations', [
        'classroom_id' => $room->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 11:00:00',
    ])->assertCreated();

    // Second reservation at overlapping time fails
    actingAs($faculty2);
    postJson('/api/v1/reservations', [
        'classroom_id' => $room->id,
        'start_at' => '2026-06-15 10:00:00',
        'end_at' => '2026-06-15 12:00:00',
    ])->assertStatus(422);

    expect(Reservation::count())->toBe(1);
});

it('allows booking different rooms at the same time', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty1 = makeFaculty(['name' => 'Faculty A']);
    $faculty2 = makeFaculty(['name' => 'Faculty B']);
    $room1 = makeRoom(['name' => 'Room 101']);
    $room2 = makeRoom(['name' => 'Room 102']);

    actingAs($faculty1);
    postJson('/api/v1/reservations', [
        'classroom_id' => $room1->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 11:00:00',
    ])->assertCreated();

    actingAs($faculty2);
    postJson('/api/v1/reservations', [
        'classroom_id' => $room2->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 11:00:00',
    ])->assertCreated();

    expect(Reservation::count())->toBe(2);
});

it('allows booking the same room at non-overlapping times', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty = makeFaculty();
    $room = makeRoom();

    actingAs($faculty);

    postJson('/api/v1/reservations', [
        'classroom_id' => $room->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
    ])->assertCreated();

    postJson('/api/v1/reservations', [
        'classroom_id' => $room->id,
        'start_at' => '2026-06-15 10:00:00',
        'end_at' => '2026-06-15 11:00:00',
    ])->assertCreated();

    expect(Reservation::count())->toBe(2);
});

// ── Update & Cancel ─────────────────────────────────────────────

it('allows a faculty to cancel their own reservation', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty = makeFaculty();
    $room = makeRoom();

    $reservation = Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'status' => 'approved',
    ]);

    actingAs($faculty);

    deleteJson("/api/v1/reservations/{$reservation->id}")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($reservation->fresh()->status)->toBe('cancelled');
    expect($reservation->fresh()->cancelled_at)->not->toBeNull();
});

it('allows updating reservation time to a non-conflicting slot', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty = makeFaculty();
    $room = makeRoom();

    $reservation = Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'status' => 'approved',
    ]);

    actingAs($faculty);

    patchJson("/api/v1/reservations/{$reservation->id}", [
        'start_at' => '2026-06-15 14:00:00',
        'end_at' => '2026-06-15 15:00:00',
    ])->assertOk();

    expect($reservation->fresh()->start_at->format('H:i'))->toBe('14:00');
});

it('rejects updating a reservation for a room marked unavailable', function () {
    Carbon::setTestNow('2026-06-15 08:00:00');

    $faculty = makeFaculty();
    $room = makeRoom([
        'status' => 'unavailable',
        'unavailable_reason' => 'Electrical inspection',
    ]);

    $reservation = Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'status' => 'approved',
    ]);

    actingAs($faculty);

    patchJson("/api/v1/reservations/{$reservation->id}", [
        'start_at' => '2026-06-15 14:00:00',
        'end_at' => '2026-06-15 15:00:00',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['classroom_id'])
        ->assertJsonPath('errors.classroom_id.0', 'Electrical inspection');

    expect($reservation->fresh()->start_at->format('H:i'))->toBe('09:00');
});

// ── ESP32 Access Check ──────────────────────────────────────────

it('grants ESP32 access when faculty has active reservation', function () {
    $now = Carbon::parse('2026-06-15 09:30:00');
    Carbon::setTestNow($now);

    $faculty = makeFaculty();
    $room = makeRoom();

    Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'status' => 'approved',
    ]);

    actingAs($faculty, 'sanctum')
        ->getJson("/api/v1/reservations/check?user_id={$faculty->id}&classroom_id={$room->id}")
        ->assertOk()
        ->assertJsonPath('allowed', true);
});

it('denies ESP32 access when reservation has expired', function () {
    $now = Carbon::parse('2026-06-15 11:00:00');
    Carbon::setTestNow($now);

    $faculty = makeFaculty();
    $room = makeRoom();

    Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'status' => 'approved',
    ]);

    actingAs($faculty, 'sanctum')
        ->getJson("/api/v1/reservations/check?user_id={$faculty->id}&classroom_id={$room->id}")
        ->assertJsonPath('allowed', false);
});

it('does not allow users to probe another users access status without card credentials', function () {
    Carbon::setTestNow('2026-06-15 09:30:00');

    $requester = makeFaculty();
    $target = makeFaculty();
    $room = makeRoom();

    Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $target->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'status' => 'approved',
    ]);

    actingAs($requester, 'sanctum')
        ->getJson("/api/v1/reservations/check?user_id={$target->id}&classroom_id={$room->id}")
        ->assertForbidden();
});

it('denies ESP32 access when RFID card does not belong to claimed user', function () {
    $now = Carbon::parse('2026-06-15 09:30:00');
    Carbon::setTestNow($now);

    $faculty1 = makeFaculty(['name' => 'Owner']);
    $faculty2 = makeFaculty(['name' => 'Impostor']);
    $room = makeRoom();

    AccessCard::create([
        'user_id' => $faculty1->id,
        'card_number' => 'CARD-001',
        'rfid_uid' => 'AA:BB:CC:DD',
        'status' => 'active',
    ]);

    Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty2->id,
        'start_at' => '2026-06-15 09:00:00',
        'end_at' => '2026-06-15 10:00:00',
        'status' => 'approved',
    ]);

    // faculty2 tries to use faculty1's RFID card
    actingAs($faculty2, 'sanctum')
        ->getJson("/api/v1/reservations/check?user_id={$faculty2->id}&classroom_id={$room->id}&rfid_uid=AA:BB:CC:DD")
        ->assertStatus(403)
        ->assertJsonPath('allowed', false);
});

it('grants ESP32 access during official class schedule', function () {
    // Friday = day_of_week 5
    $now = Carbon::parse('2026-06-20 10:30:00'); // a Friday
    Carbon::setTestNow($now);

    $faculty = makeFaculty();
    $room = makeRoom();

    $course = Course::create([
        'code' => 'IT101',
        'title' => 'Intro to IT',
        'description' => 'Test',
        'instructor_user_id' => $faculty->id,
        'capacity' => 40,
    ]);

    Schedule::create([
        'classroom_id' => $room->id,
        'course_id' => $course->id,
        'start_at' => '2026-06-20 10:00:00',
        'end_at' => '2026-06-20 12:00:00',
        'status' => 'scheduled',
        'day_of_week' => $now->dayOfWeek,
        'enrolled' => 30,
    ]);

    actingAs($faculty, 'sanctum')
        ->getJson("/api/v1/reservations/check?user_id={$faculty->id}&classroom_id={$room->id}")
        ->assertOk()
        ->assertJsonPath('allowed', true)
        ->assertJsonPath('message', 'Access granted during official class schedule');
});

it('denies ESP32 access with no reservation and no schedule', function () {
    Carbon::setTestNow('2026-06-15 10:00:00');

    $faculty = makeFaculty();
    $room = makeRoom();

    actingAs($faculty, 'sanctum')
        ->getJson("/api/v1/reservations/check?user_id={$faculty->id}&classroom_id={$room->id}")
        ->assertJsonPath('allowed', false)
        ->assertJsonPath('message', 'No schedule');
});
