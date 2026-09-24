<?php

use App\Models\AccessCard;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows an admin to create an RFID access card', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $instructor = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'department' => 'Computer Science',
    ]);
    $classroom = Classroom::create([
        'name' => 'CS Lab 301',
        'building' => 'Engineering Building',
    ]);

    actingAs($admin)
        ->postJson(route('admin.access-cards.store'), [
            'user_id' => $instructor->id,
            'classroom_id' => $classroom->id,
            'card_number' => 'CARD-0002',
            'rfid_uid' => 'RFID-A1B2C3D4',
            'status' => 'active',
            'expires_at' => '2027-09-19',
        ])
        ->assertCreated()
        ->assertJsonPath('data.card_number', 'CARD-0002');

    expect(AccessCard::query()->where('rfid_uid', 'A1B2C3D4')->exists())->toBeTrue();
});

it('rejects duplicate card numbers and RFID UIDs', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $instructor = User::factory()->create(['role' => 'faculty', 'status' => 'active']);

    AccessCard::create([
        'user_id' => $instructor->id,
        'card_number' => 'CARD-0002',
        'rfid_uid' => 'A1B2C3D4',
        'status' => 'active',
    ]);

    actingAs($admin)
        ->postJson(route('admin.access-cards.store'), [
            'user_id' => $instructor->id,
            'card_number' => 'CARD-0002',
            'rfid_uid' => 'RFID-A1B2C3D4',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['card_number', 'rfid_uid']);
});

it('allows access during the instructor official class schedule', function () {
    $instructor = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);
    $classroom = Classroom::create([
        'name' => 'Room 15',
        'building' => 'Main Building',
    ]);
    $course = Course::create([
        'code' => 'STS101',
        'title' => 'Science, Technology and Society',
        'instructor_user_id' => $instructor->id,
        'classroom_id' => $classroom->id,
    ]);
    $now = Carbon::parse('2026-09-19 13:30:00');

    Schedule::create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => $now->copy()->startOfDay()->setTime(13, 0),
        'end_at' => $now->copy()->startOfDay()->setTime(15, 0),
        'day_of_week' => $now->dayOfWeek,
        'status' => 'scheduled',
    ]);

    $this->travelTo($now);

    actingAs($instructor, 'sanctum')
        ->getJson('/api/v1/reservations/check?user_id='.$instructor->id.'&classroom_id='.$classroom->id)
        ->assertOk()
        ->assertJsonPath('allowed', true)
        ->assertJsonPath('schedule_id', 1);
});

it('resolves the faculty owner when an RFID scan is logged by UID', function () {
    $instructor = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);
    $classroom = Classroom::create([
        'name' => 'Room 15',
        'building' => 'Main Building',
    ]);
    $card = AccessCard::create([
        'user_id' => $instructor->id,
        'classroom_id' => $classroom->id,
        'card_number' => 'CARD-UID-0001',
        'rfid_uid' => 'RFID-87:3E:D2:06',
        'status' => 'active',
    ]);

    actingAs($instructor, 'sanctum')
        ->postJson('/api/v1/access-logs', [
            'classroom_id' => $classroom->id,
            'direction' => 'entry',
            'result' => 'denied',
            'reason' => 'No Schedule',
            'accessed_at' => now()->toIso8601String(),
            'metadata' => [
                'method' => 'RFID',
                'rfid_uid' => '87-3e-d2-06',
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.user_id', $instructor->id)
        ->assertJsonPath('data.access_card_id', $card->id);
});

it('finds a prefixed faculty RFID card from the ESP32 UID', function () {
    $instructor = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);
    $card = AccessCard::create([
        'user_id' => $instructor->id,
        'card_number' => 'CARD-PREFIX-0001',
        'rfid_uid' => 'RFID-87:3E:D2:06',
        'status' => 'active',
    ]);

    $this->getJson('/api/v1/access-cards?rfid_uid=87%3A3E%3AD2%3A06')
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $card->id)
        ->assertJsonPath('data.0.user_id', $instructor->id);
});

it('stores a manually entered RFID without the RFID display prefix', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $instructor = User::factory()->create(['role' => 'faculty', 'status' => 'active']);

    actingAs($admin)
        ->postJson(route('admin.access-cards.store'), [
            'user_id' => $instructor->id,
            'card_number' => 'CARD-RAW-0001',
            'rfid_uid' => 'RFID-87:3E:D2:06',
        ])
        ->assertCreated()
        ->assertJsonPath('data.rfid_uid', '87:3E:D2:06');
});
test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
