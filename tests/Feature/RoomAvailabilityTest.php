<?php

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
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function createFacultyUser(): User {
    return User::create([
        'name' => 'Faculty Tester',
        'email' => 'faculty.tester@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'department' => 'Information Technology',
        'must_change_password' => false,
    ]);
}

it('blocks reservation when room overlaps an existing class schedule', function () {
    Carbon::setTestNow('2026-04-11 08:00:00');

    $faculty = createFacultyUser();

    $classroom = Classroom::create([
        'name' => 'Room 201',
        'building' => 'Building A',
        'floor' => '2nd Floor',
        'capacity' => 40,
    ]);

    $course = Course::create([
        'code' => 'CS999',
        'title' => 'Advanced Testing',
        'description' => 'Test course',
        'instructor_user_id' => $faculty->id,
        'capacity' => 40,
    ]);

    Schedule::create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-11 09:00:00',
        'end_at' => '2026-04-11 11:00:00',
        'status' => 'scheduled',
        'day_of_week' => 0,
        'enrolled' => 20,
    ]);

    actingAs($faculty);

    $response = postJson('/api/v1/reservations', [
        'classroom_id' => $classroom->id,
        'start_at' => '2026-04-11 10:00:00',
        'end_at' => '2026-04-11 12:00:00',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Room is occupied by official schedule at selected time.');

    expect(Reservation::count())->toBe(0);
});

it('blocks reservation when room overlaps an existing reservation', function () {
    Carbon::setTestNow('2026-04-11 08:00:00');

    $faculty = createFacultyUser();

    $classroom = Classroom::create([
        'name' => 'Room 301',
        'building' => 'Building B',
        'floor' => '3rd Floor',
        'capacity' => 35,
    ]);

    Reservation::create([
        'classroom_id' => $classroom->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-04-11 13:00:00',
        'end_at' => '2026-04-11 15:00:00',
        'status' => 'reserved',
    ]);

    actingAs($faculty);

    $response = postJson('/api/v1/reservations', [
        'classroom_id' => $classroom->id,
        'start_at' => '2026-04-11 14:00:00',
        'end_at' => '2026-04-11 16:00:00',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Room is already reserved at selected time.');

    expect(Reservation::query()->where('status', 'reserved')->count())->toBe(1);
});

it('returns occupied only for current classes and available before future bookings', function () {
    Carbon::setTestNow('2026-04-11 10:00:00');

    $faculty = createFacultyUser();

    $occupiedRoom = Classroom::create([
        'name' => 'Room OCC',
        'building' => 'Building C',
        'floor' => '1st Floor',
        'capacity' => 30,
    ]);

    $reservedRoom = Classroom::create([
        'name' => 'Room RES',
        'building' => 'Building C',
        'floor' => '2nd Floor',
        'capacity' => 30,
    ]);

    $course = Course::create([
        'code' => 'IT777',
        'title' => 'Realtime Systems',
        'description' => 'Test course',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);

    Schedule::create([
        'classroom_id' => $occupiedRoom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-11 09:30:00',
        'end_at' => '2026-04-11 11:00:00',
        'status' => 'ongoing',
        'day_of_week' => 0,
        'enrolled' => 18,
    ]);

    Reservation::create([
        'classroom_id' => $reservedRoom->id,
        'user_id' => $faculty->id,
        'start_at' => '2026-04-11 10:15:00',
        'end_at' => '2026-04-11 10:45:00',
        'status' => 'reserved',
    ]);

    actingAs($faculty);

    $response = getJson('/api/v1/room-statuses?start_at=2026-04-11T10:00:00&end_at=2026-04-11T11:00:00');

    $response->assertOk();

    $statuses = collect($response->json('data'));

    expect($statuses->firstWhere('classroom_id', $occupiedRoom->id)['status'])->toBe('occupied');
    expect($statuses->firstWhere('classroom_id', $reservedRoom->id)['status'])->toBe('available');
    expect($statuses->firstWhere('classroom_id', $reservedRoom->id)['time_info'])->toBe('Until 10:15 AM');
});

    it('returns the faculty member upcoming reservations for the rooms screen', function () {
        $faculty = createFacultyUser();
        $classroom = Classroom::create([
            'name' => 'Room Booking',
            'building' => 'Building A',
        ]);

        $reservation = Reservation::create([
            'classroom_id' => $classroom->id,
            'user_id' => $faculty->id,
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
            'status' => 'approved',
        ]);

        actingAs($faculty)
            ->getJson(route('faculty.reservations.mine'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $reservation->id)
            ->assertJsonPath('data.0.classroom.name', 'Room Booking');
    });

    it('allows a reserved faculty RFID card with a prefixed stored UID', function () {
        $faculty = createFacultyUser();
        $classroom = Classroom::create([
            'name' => 'Room 15',
            'building' => 'Building A',
        ]);
        $card = AccessCard::create([
            'user_id' => $faculty->id,
            'classroom_id' => $classroom->id,
            'card_number' => 'CARD-RESERVED-0001',
            'rfid_uid' => 'RFID-87:3E:D2:06',
            'status' => 'active',
        ]);
        $now = Carbon::parse('2026-09-19 18:45:00');

        Reservation::create([
            'classroom_id' => $classroom->id,
            'user_id' => $faculty->id,
            'start_at' => $now->copy()->subMinutes(5),
            'end_at' => $now->copy()->addMinutes(5),
            'status' => 'approved',
        ]);

        $this->travelTo($now);

        actingAs($faculty, 'sanctum')
            ->getJson('/api/v1/reservations/check?user_id='.$faculty->id.'&classroom_id='.$classroom->id.'&rfid_uid=87%3A3E%3AD2%3A06')
            ->assertOk()
            ->assertJsonPath('allowed', true);

        expect($card->fresh()->status)->toBe('active');
    });

it('allows room reservations outside the temporary operating-hours restriction', function () {
    $faculty = createFacultyUser();

    $classroom = Classroom::create([
        'name' => 'Room Hours',
        'building' => 'Building D',
        'floor' => '1st Floor',
        'capacity' => 30,
    ]);

    actingAs($faculty);

    Carbon::setTestNow('2026-04-11 17:30:00');
    getJson('/api/v1/room-statuses')
        ->assertOk()
        ->assertJsonPath('data.0.status', 'available')
        ->assertJsonPath('data.0.status_label', 'Available');

    Carbon::setTestNow('2026-04-12 06:00:00');
    getJson('/api/v1/room-statuses')
        ->assertOk()
        ->assertJsonPath('data.0.status', 'available');
});

it('reports a room as occupied when live occupancy is detected', function () {
    Carbon::setTestNow('2026-04-11 10:00:00');

    $faculty = createFacultyUser();

    $classroom = Classroom::create([
        'name' => 'Room Sensor',
        'building' => 'Building E',
        'floor' => '1st Floor',
        'capacity' => 30,
        'current_occupancy' => 4,
    ]);

    actingAs($faculty);

    getJson('/api/v1/room-statuses')
        ->assertOk()
        ->assertJsonPath('data.0.classroom_id', $classroom->id)
        ->assertJsonPath('data.0.status', 'occupied')
        ->assertJsonPath('data.0.status_label', 'Occupied');
});

it('allows reservations during the temporary all-day testing window', function () {
    Carbon::setTestNow('2026-04-11 17:00:00');

    $faculty = createFacultyUser();

    $classroom = Classroom::create([
        'name' => 'Room Closed',
        'building' => 'Building F',
        'floor' => '1st Floor',
        'capacity' => 30,
    ]);

    actingAs($faculty);

    postJson('/api/v1/reservations', [
        'classroom_id' => $classroom->id,
        'start_at' => '2026-04-11 17:00:00',
        'end_at' => '2026-04-11 18:00:00',
    ])->assertCreated();

    expect(Reservation::count())->toBe(1);
});
