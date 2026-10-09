<?php

use App\Models\AccessCard;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Device;
use App\Models\Enrollment;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('requires a registered device credential for its heartbeat', function () {
    $this->postJson('/api/v1/device/heartbeat')
        ->assertUnauthorized();
});

it('authenticates a registered device without a user account', function () {
    $classroom = Classroom::create([
        'name' => 'Room 15',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $credential = str_repeat('a', 64);
    $device = Device::create([
        'name' => 'Room 15 Door',
        'classroom_id' => $classroom->id,
        'status' => 'active',
        'credential_hash' => hash('sha256', $credential),
    ]);

    $this->withToken($credential)
        ->postJson('/api/v1/device/heartbeat')
        ->assertOk()
        ->assertJsonPath('device_id', $device->id)
        ->assertJsonPath('classroom_id', $classroom->id);

    expect($device->fresh()->last_seen_at)->not->toBeNull();
    $this->assertDatabaseMissing('users', ['email' => 'room15-door@smartroom.local']);
});

it('registers a device for an ESP32 room without creating a user account', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $classroom = Classroom::create([
        'name' => 'Room 16',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);

    $response = $this->actingAs($admin)
        ->postJson(route('admin.classrooms.devices.store', $classroom), ['name' => 'Main Door']);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Main Door');

    $credential = $response->json('data.credential');
    $device = Device::query()->where('name', 'Main Door')->firstOrFail();

    expect($credential)->toBeString()->toHaveLength(64)
        ->and($device->credential_hash)->toBe(hash('sha256', $credential))
        ->and(User::query()->count())->toBe(1);
});

it('does not allow device registration in a manual room', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $classroom = Classroom::create([
        'name' => 'Manual Room',
        'building' => 'Main Building',
        'access_mode' => 'manual',
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.classrooms.devices.store', $classroom), ['name' => 'Main Door'])
        ->assertUnprocessable();

    $this->assertDatabaseMissing('devices', ['name' => 'Main Door']);
});

it('provisions a room device from the console without an account', function () {
    $classroom = Classroom::create([
        'name' => 'CLI Room',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);

    $this->artisan('create:device', [
        'classroom_id' => $classroom->id,
        'name' => 'CLI Door',
    ])->assertSuccessful();

    $device = Device::query()->where('name', 'CLI Door')->firstOrFail();
    expect($device->credential_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and(User::query()->count())->toBe(0);
});

it('rotates a device credential and revokes its linked legacy tokens', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $classroom = Classroom::create([
        'name' => 'Rotation Room',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $oldCredential = str_repeat('a', 64);
    $device = Device::create([
        'name' => 'Rotation Door',
        'classroom_id' => $classroom->id,
        'status' => 'active',
        'credential_hash' => hash('sha256', $oldCredential),
    ]);
    $legacyUser = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $legacyToken = $legacyUser->createToken('legacy-door', ['device:access']);
    $legacyToken->accessToken->forceFill(['device_id' => $device->id])->save();

    $response = $this->actingAs($admin)
        ->postJson(route('admin.classrooms.devices.credential', [$classroom, $device]));

    $response->assertOk();
    $newCredential = $response->json('data.credential');
    expect($device->fresh()->credential_hash)->toBe(hash('sha256', $newCredential));
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $legacyToken->accessToken->id]);

    $this->withToken($oldCredential)
        ->postJson('/api/v1/device/heartbeat')
        ->assertUnauthorized();

    $this->withToken($newCredential)
        ->postJson('/api/v1/device/heartbeat')
        ->assertOk();
});

it('binds device reservation checks to its registered room', function () {
    $registeredRoom = Classroom::create([
        'name' => 'Registered Room',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $otherRoom = Classroom::create([
        'name' => 'Other Room',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $user = User::factory()->create(['status' => 'active']);
    $credential = str_repeat('b', 64);
    Device::create([
        'name' => 'Registered Door',
        'classroom_id' => $registeredRoom->id,
        'status' => 'active',
        'credential_hash' => hash('sha256', $credential),
    ]);

    $this->withToken($credential)
        ->getJson('/api/v1/device/reservations/check?user_id='.$user->id.'&classroom_id='.$otherRoom->id)
        ->assertForbidden();
});

it('requires RFID proof before a device can check a users reservation', function () {
    $now = Carbon::parse('2026-10-03 10:30:00');
    $this->travelTo($now);
    $classroom = Classroom::create([
        'name' => 'Reservation Room',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $user = User::factory()->create(['status' => 'active']);
    Reservation::create([
        'classroom_id' => $classroom->id,
        'user_id' => $user->id,
        'start_at' => $now->copy()->subMinutes(30),
        'end_at' => $now->copy()->addMinutes(30),
        'status' => 'approved',
    ]);
    $credential = str_repeat('g', 64);
    Device::create([
        'name' => 'Reservation Door',
        'classroom_id' => $classroom->id,
        'status' => 'active',
        'credential_hash' => hash('sha256', $credential),
    ]);

    $this->withToken($credential)
        ->getJson('/api/v1/device/reservations/check?user_id='.$user->id)
        ->assertForbidden();
});

it('denies device access for a current unapproved reservation owned by the scanned cardholder', function () {
    $now = Carbon::parse('2026-10-03 10:30:00');
    $this->travelTo($now);
    $classroom = Classroom::create([
        'name' => 'Reserved Access Room',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $user = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $card = AccessCard::create([
        'user_id' => $user->id,
        'card_number' => 'RESERVED-ACCESS-CARD',
        'rfid_uid' => '87:3E:D2:06',
        'status' => 'active',
    ]);
    $reservation = Reservation::create([
        'classroom_id' => $classroom->id,
        'user_id' => $user->id,
        'start_at' => $now->copy()->subMinutes(30),
        'end_at' => $now->copy()->addMinutes(30),
        'status' => 'reserved',
    ]);
    $credential = str_repeat('r', 64);
    Device::create([
        'name' => 'Reserved Access Door',
        'classroom_id' => $classroom->id,
        'status' => 'active',
        'credential_hash' => hash('sha256', $credential),
    ]);

    $this->withToken($credential)
        ->getJson('/api/v1/device/reservations/check?user_id='.$user->id.'&rfid_uid='.$card->rfid_uid)
        ->assertSuccessful()
        ->assertJsonPath('allowed', false)
        ->assertJsonPath('reason', "Reservation status is 'reserved', not approved.");
});

it('denies access to inactive users even when their card and reservation are active', function () {
    $now = Carbon::parse('2026-10-03 10:30:00');
    $this->travelTo($now);
    $classroom = Classroom::create([
        'name' => 'Inactive User Room',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $user = User::factory()->create(['role' => 'faculty', 'status' => 'inactive']);
    $card = AccessCard::create([
        'user_id' => $user->id,
        'card_number' => 'INACTIVE-USER-CARD',
        'rfid_uid' => 'DE:AD:BE:EF',
        'status' => 'active',
    ]);
    Reservation::create([
        'classroom_id' => $classroom->id,
        'user_id' => $user->id,
        'start_at' => $now->copy()->subMinutes(30),
        'end_at' => $now->copy()->addMinutes(30),
        'status' => 'approved',
    ]);
    $credential = str_repeat('h', 64);
    Device::create([
        'name' => 'Inactive User Door',
        'classroom_id' => $classroom->id,
        'status' => 'active',
        'credential_hash' => hash('sha256', $credential),
    ]);

    $this->withToken($credential)
        ->getJson('/api/v1/device/reservations/check?user_id='.$user->id.'&rfid_uid='.$card->rfid_uid)
        ->assertSuccessful()
        ->assertJsonPath('allowed', false);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reservations/check?user_id='.$user->id.'&classroom_id='.$classroom->id)
        ->assertForbidden()
        ->assertJsonPath('allowed', false);
});

it('does not let an authenticated user inspect another cardholders schedule', function () {
    $requester = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $cardholder = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $classroom = Classroom::create([
        'name' => 'Private Schedule Room',
        'building' => 'Main Building',
        'access_mode' => 'manual',
    ]);
    $card = AccessCard::create([
        'user_id' => $cardholder->id,
        'card_number' => 'PRIVATE-SCHEDULE-CARD',
        'rfid_uid' => 'BA:BE:CA:FE',
        'status' => 'active',
    ]);

    $this->actingAs($requester, 'sanctum')
        ->getJson('/api/v1/reservations/check?user_id='.$cardholder->id.'&classroom_id='.$classroom->id.'&rfid_uid='.$card->rfid_uid)
        ->assertForbidden();
});

it('restricts classroom course and enrollment mutations to admins', function () {
    $faculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $studentUser = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'ROLE-CHECK-STUDENT',
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'status' => 'active',
    ]);
    $course = Course::create([
        'code' => 'ROLE-CHECK-101',
        'title' => 'Role Check',
        'instructor_user_id' => $faculty->id,
    ]);

    $this->actingAs($faculty)
        ->postJson('/api/v1/classrooms', [
            'name' => 'Unauthorized Room',
            'building' => 'Main Building',
            'capacity' => 20,
            'access_mode' => 'manual',
        ])
        ->assertForbidden();

    $this->postJson('/api/v1/courses', [
        'code' => 'UNAUTHORIZED-101',
        'title' => 'Unauthorized Course',
        'instructor_user_id' => $faculty->id,
    ])->assertForbidden();

    $this->postJson('/api/v1/enrollments', [
        'student_id' => $student->id,
        'course_id' => $course->id,
    ])->assertForbidden();

    $this->assertDatabaseMissing('classrooms', ['name' => 'Unauthorized Room']);
    $this->assertDatabaseMissing('courses', ['code' => 'UNAUTHORIZED-101']);
    $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id, 'course_id' => $course->id]);
});

it('returns only the card fields needed by a device', function () {
    $classroom = Classroom::create([
        'name' => 'Room 15',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $user = User::factory()->create(['status' => 'active']);
    $card = AccessCard::create([
        'user_id' => $user->id,
        'card_number' => 'DEVICE-CARD-1',
        'rfid_uid' => 'A1:B2:C3:D4',
        'status' => 'active',
    ]);
    $credential = str_repeat('c', 64);
    Device::create([
        'name' => 'Room 15 Door',
        'classroom_id' => $classroom->id,
        'status' => 'active',
        'credential_hash' => hash('sha256', $credential),
    ]);

    $this->withToken($credential)
        ->getJson('/api/v1/device/access-cards?rfid_uid=A1%3AB2%3AC3%3AD4')
        ->assertOk()
        ->assertJsonPath('data.0.id', $card->id)
        ->assertJsonMissingPath('data.0.rfid_uid')
        ->assertJsonMissingPath('data.0.user.email');
});

it('returns the matched inactive card status to the authorized door device', function () {
    $classroom = Classroom::create([
        'name' => 'Inactive Card Room',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $user = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $card = AccessCard::create([
        'user_id' => $user->id,
        'card_number' => 'DEVICE-INACTIVE-CARD',
        'rfid_uid' => 'A1:B2:C3:D5',
        'status' => 'inactive',
    ]);
    $credential = str_repeat('d', 64);
    Device::create([
        'name' => 'Inactive Card Door',
        'classroom_id' => $classroom->id,
        'status' => 'active',
        'credential_hash' => hash('sha256', $credential),
    ]);

    $this->withToken($credential)
        ->getJson('/api/v1/device/access-cards?rfid_uid=A1%3AB2%3AC3%3AD5')
        ->assertOk()
        ->assertJsonPath('data.0.id', $card->id)
        ->assertJsonPath('data.0.status', 'inactive');
});

it('saves the selected room access mode and keeps legacy RFID state in sync', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.classrooms.store'), [
            'name' => 'Controlled Room',
            'building' => 'Main Building',
            'capacity' => 30,
            'status' => 'available',
            'access_mode' => 'esp32',
        ])
        ->assertCreated()
        ->assertJsonPath('data.access_mode', 'esp32')
        ->assertJsonPath('data.rfid_status', 'active');
});

it('renders room access mode and device health in the admin views', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $classroom = Classroom::create([
        'name' => 'Visible Device Room',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
        'rfid_status' => 'active',
    ]);
    Device::create([
        'name' => 'Visible Door',
        'classroom_id' => $classroom->id,
        'status' => 'active',
        'credential_hash' => hash('sha256', str_repeat('e', 64)),
        'last_seen_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.classrooms'))
        ->assertOk()
        ->assertSee('ESP32-controlled')
        ->assertSee('Online');

    $this->get(route('admin.classrooms.show', $classroom->id))
        ->assertOk()
        ->assertSee('Door devices')
        ->assertSee('Visible Door')
        ->assertSee('Rotate key');
});

it('denies an actively enrolled student who is not the scheduled instructor in the device room', function () {
    $now = Carbon::parse('2026-10-02 10:30:00');
    $this->travelTo($now);
    $room = Classroom::create([
        'name' => 'Classroom 15',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $studentUser = User::factory()->create(['role' => 'student', 'status' => 'active']);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'student_id' => 'STUDENT-DEVICE-01',
        'name' => $studentUser->name,
        'email' => $studentUser->email,
        'status' => 'active',
    ]);
    $instructor = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $course = Course::create([
        'code' => 'ROOM-ACCESS-101',
        'title' => 'Room Access Test',
        'instructor_user_id' => $instructor->id,
    ]);
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'enrolled_at' => $now,
        'status' => 'active',
    ]);
    Schedule::create([
        'classroom_id' => $room->id,
        'course_id' => $course->id,
        'start_at' => $now->copy()->startOfDay()->setTime(10, 0),
        'end_at' => $now->copy()->startOfDay()->setTime(12, 0),
        'day_of_week' => $now->dayOfWeek,
        'status' => 'scheduled',
    ]);
    $card = AccessCard::create([
        'user_id' => $studentUser->id,
        'card_number' => 'STUDENT-DEVICE-CARD',
        'rfid_uid' => '12:34:56:78',
        'status' => 'active',
    ]);
    $credential = str_repeat('f', 64);
    Device::create([
        'name' => 'Room 15 Door',
        'classroom_id' => $room->id,
        'status' => 'active',
        'credential_hash' => hash('sha256', $credential),
    ]);

    $this->withToken($credential)
        ->getJson('/api/v1/device/reservations/check?user_id='.$studentUser->id.'&rfid_uid='.$card->rfid_uid)
        ->assertOk()
        ->assertJsonPath('allowed', false);

    $this->withToken($credential)
        ->postJson('/api/v1/device/access-logs', [
            'user_id' => $studentUser->id,
            'direction' => 'entry',
            'result' => 'granted',
            'accessed_at' => $now->toIso8601String(),
            'metadata' => ['method' => 'RFID', 'rfid_uid' => $card->rfid_uid],
        ])
        ->assertCreated()
        ->assertJsonPath('data.result', 'denied')
        ->assertJsonPath('data.classroom_id', $room->id);
});
