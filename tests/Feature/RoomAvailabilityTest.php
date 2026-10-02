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

function createFacultyUser(): User
{
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

it('does not expose course or instructor details in public availability checks', function () {
    $faculty = createFacultyUser();
    $classroom = Classroom::create([
        'name' => 'Room Cancelled',
        'building' => 'IT Building',
        'capacity' => 30,
    ]);
    $course = Course::create([
        'code' => 'IT-CANCELLED',
        'title' => 'Private Cancelled Course',
        'description' => 'Private course',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);
    Schedule::create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-11 09:00:00',
        'end_at' => '2026-04-11 11:00:00',
        'status' => 'cancelled',
        'day_of_week' => 0,
        'enrolled' => 0,
    ]);

    getJson('/api/v1/room-availability/check?classroom_id='.$classroom->id.'&start_at=2026-04-11T09:00:00&end_at=2026-04-11T11:00:00')
        ->assertOk()
        ->assertJsonPath('data.cancelled_classes.0.schedule_id', Schedule::first()->id)
        ->assertJsonMissingPath('data.cancelled_classes.0.subject')
        ->assertJsonMissingPath('data.cancelled_classes.0.course_code')
        ->assertJsonMissingPath('data.cancelled_classes.0.instructor');
});

it('only exposes IT cancelled classes in public availability checks', function () {
    $itFaculty = createFacultyUser();
    $otherFaculty = User::create([
        'name' => 'Other Cancelled Faculty',
        'email' => 'other.cancelled.faculty@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'department' => 'Business Administration',
        'must_change_password' => false,
    ]);
    $room = Classroom::create(['name' => 'Cancelled Scope Room', 'building' => 'IT Building', 'capacity' => 30]);
    $itCourse = Course::create([
        'code' => 'IT-CANCEL-SCOPE',
        'title' => 'IT Cancelled Scope',
        'description' => 'Scope fixture',
        'instructor_user_id' => $itFaculty->id,
        'capacity' => 30,
    ]);
    $otherCourse = Course::create([
        'code' => 'BUS-CANCEL-SCOPE',
        'title' => 'Business Cancelled Scope',
        'description' => 'Scope fixture',
        'instructor_user_id' => $otherFaculty->id,
        'capacity' => 30,
    ]);
    $itSchedule = Schedule::create([
        'classroom_id' => $room->id,
        'course_id' => $itCourse->id,
        'start_at' => '2026-04-11 09:00:00',
        'end_at' => '2026-04-11 10:00:00',
        'status' => 'cancelled',
        'day_of_week' => 0,
        'enrolled' => 0,
    ]);
    Schedule::create([
        'classroom_id' => $room->id,
        'course_id' => $otherCourse->id,
        'start_at' => '2026-04-11 10:00:00',
        'end_at' => '2026-04-11 11:00:00',
        'status' => 'cancelled',
        'day_of_week' => 0,
        'enrolled' => 0,
    ]);

    getJson('/api/v1/room-availability/check?classroom_id='.$room->id.'&start_at=2026-04-11T09:00:00&end_at=2026-04-11T12:00:00')
        ->assertOk()
        ->assertJsonCount(1, 'data.cancelled_classes')
        ->assertJsonPath('data.cancelled_classes.0.schedule_id', $itSchedule->id);
});

it('does not expose non-IT conflicts for an IT-scoped room status', function () {
    $itFaculty = createFacultyUser();
    $otherFaculty = User::create([
        'name' => 'Other Status Faculty',
        'email' => 'other.status.faculty@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'department' => 'Business Administration',
        'must_change_password' => false,
    ]);
    $room = Classroom::create(['name' => 'Shared IT Room', 'building' => 'IT Building', 'capacity' => 30]);
    $itCourse = Course::create([
        'code' => 'IT-SCOPE',
        'title' => 'IT Scope',
        'description' => 'Scope fixture',
        'instructor_user_id' => $itFaculty->id,
        'capacity' => 30,
    ]);
    $otherCourse = Course::create([
        'code' => 'BUS-SCOPE',
        'title' => 'Business Scope',
        'description' => 'Scope fixture',
        'instructor_user_id' => $otherFaculty->id,
        'capacity' => 30,
    ]);
    Schedule::create([
        'classroom_id' => $room->id,
        'course_id' => $itCourse->id,
        'start_at' => '2026-04-11 09:00:00',
        'end_at' => '2026-04-11 10:00:00',
        'status' => 'cancelled',
        'day_of_week' => 0,
        'enrolled' => 0,
    ]);
    $otherSchedule = Schedule::create([
        'classroom_id' => $room->id,
        'course_id' => $otherCourse->id,
        'start_at' => '2026-04-11 10:00:00',
        'end_at' => '2026-04-11 11:00:00',
        'status' => 'scheduled',
        'day_of_week' => 0,
        'enrolled' => 0,
    ]);

    $response = getJson('/api/v1/room-statuses?start_at=2026-04-11T09:00:00&end_at=2026-04-11T12:00:00')
        ->assertOk();

    expect(collect($response->json('data.0.conflicts'))->pluck('id'))
        ->not->toContain($otherSchedule->id);
});

it('does not expose non-IT reservations in public fixed room schedules', function () {
    $itFaculty = createFacultyUser();
    $otherFaculty = User::create([
        'name' => 'Other Map Faculty',
        'email' => 'other.map.faculty@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'department' => 'Business Administration',
        'must_change_password' => false,
    ]);
    $room = Classroom::create(['name' => 'IT Reservation Room', 'building' => 'IT Building', 'capacity' => 30]);
    $course = Course::create([
        'code' => 'IT-MAP',
        'title' => 'IT Map',
        'description' => 'Scope fixture',
        'instructor_user_id' => $itFaculty->id,
        'capacity' => 30,
    ]);
    Schedule::create([
        'classroom_id' => $room->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-11 09:00:00',
        'end_at' => '2026-04-11 10:00:00',
        'status' => 'cancelled',
        'day_of_week' => 0,
        'enrolled' => 0,
    ]);
    $otherReservation = Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $otherFaculty->id,
        'start_at' => '2026-04-11 10:00:00',
        'end_at' => '2026-04-11 11:00:00',
        'status' => 'approved',
    ]);

    getJson("/api/v1/map/rooms/{$room->id}/fixed-schedules?date=2026-04-11")
        ->assertOk()
        ->assertJsonMissing(['id' => $otherReservation->id]);
});

it('blocks official schedules in unavailable classrooms', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'department' => 'Information Technology',
    ]);
    $instructor = createFacultyUser();
    $room = Classroom::create([
        'name' => 'Unavailable Official Room',
        'building' => 'IT Building',
        'capacity' => 30,
        'status' => 'maintenance',
    ]);
    $course = Course::create([
        'code' => 'IT-BLOCKED',
        'title' => 'Blocked Official Class',
        'description' => 'Scope fixture',
        'instructor_user_id' => $instructor->id,
        'capacity' => 30,
    ]);

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/schedules', [
            'classroom_id' => $room->id,
            'course_id' => $course->id,
            'start_at' => '2026-04-11 09:00:00',
            'end_at' => '2026-04-11 10:00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('classroom_id');

    expect(Schedule::query()->where('classroom_id', $room->id)->exists())->toBeFalse();
});

it('does not allow non-admin users to delete official schedules', function () {
    $faculty = createFacultyUser();
    $room = Classroom::create(['name' => 'Protected Schedule Room', 'building' => 'IT Building', 'capacity' => 30]);
    $course = Course::create([
        'code' => 'IT-DELETE-PROTECTED',
        'title' => 'Protected Schedule',
        'description' => 'Authorization fixture',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);
    $schedule = Schedule::create([
        'classroom_id' => $room->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-11 09:00:00',
        'end_at' => '2026-04-11 10:00:00',
        'status' => 'scheduled',
        'day_of_week' => 0,
        'enrolled' => 0,
    ]);
    $student = User::factory()->create(['role' => 'student']);

    actingAs($student, 'sanctum')
        ->deleteJson("/api/v1/schedules/{$schedule->id}")
        ->assertForbidden();

    expect(Schedule::query()->whereKey($schedule->id)->exists())->toBeTrue();
});

it('allows admins to delete IT official schedules', function () {
    $faculty = createFacultyUser();
    $room = Classroom::create(['name' => 'Admin Schedule Room', 'building' => 'IT Building', 'capacity' => 30]);
    $course = Course::create([
        'code' => 'IT-DELETE-ADMIN',
        'title' => 'Admin Deletable Schedule',
        'description' => 'Authorization fixture',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);
    $schedule = Schedule::create([
        'classroom_id' => $room->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-11 09:00:00',
        'end_at' => '2026-04-11 10:00:00',
        'status' => 'scheduled',
        'day_of_week' => 0,
        'enrolled' => 0,
    ]);
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/schedules/{$schedule->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Schedule deleted successfully.');

    expect(Schedule::query()->whereKey($schedule->id)->exists())->toBeFalse();
});

it('limits public room statuses to IT-scoped classrooms', function () {
    $itFaculty = createFacultyUser();
    $otherFaculty = User::create([
        'name' => 'Business Faculty',
        'email' => 'business.faculty@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'department' => 'Business Administration',
        'must_change_password' => false,
    ]);
    $itRoom = Classroom::create(['name' => 'IT Status Room', 'building' => 'IT Building', 'capacity' => 30]);
    $otherRoom = Classroom::create(['name' => 'Business Status Room', 'building' => 'Business Building', 'capacity' => 30]);
    $itCourse = Course::create([
        'code' => 'IT-STATUS',
        'title' => 'IT Status',
        'description' => 'Scope fixture',
        'instructor_user_id' => $itFaculty->id,
        'capacity' => 30,
    ]);
    $otherCourse = Course::create([
        'code' => 'BUS-STATUS',
        'title' => 'Business Status',
        'description' => 'Scope fixture',
        'instructor_user_id' => $otherFaculty->id,
        'capacity' => 30,
    ]);
    foreach ([$itRoom->id => $itCourse->id, $otherRoom->id => $otherCourse->id] as $classroomId => $courseId) {
        Schedule::create([
            'classroom_id' => $classroomId,
            'course_id' => $courseId,
            'start_at' => '2026-04-10 09:00:00',
            'end_at' => '2026-04-10 10:00:00',
            'status' => 'cancelled',
            'day_of_week' => 5,
            'enrolled' => 0,
        ]);
    }

    getJson('/api/v1/room-statuses')
        ->assertOk()
        ->assertJsonFragment(['classroom_id' => $itRoom->id])
        ->assertJsonMissing(['classroom_id' => $otherRoom->id]);
});

it('limits public map rooms to classrooms associated with IT users', function () {
    $itFaculty = createFacultyUser();
    $otherFaculty = User::create([
        'name' => 'Other Faculty',
        'email' => 'other.faculty@example.com',
        'password' => Hash::make('Password123!'),
        'role' => 'faculty',
        'department' => 'Business Administration',
        'must_change_password' => false,
    ]);

    $itRoom = Classroom::create([
        'name' => 'IT Room',
        'building' => 'IT Building',
        'capacity' => 30,
    ]);
    $otherRoom = Classroom::create([
        'name' => 'Business Room',
        'building' => 'Business Building',
        'capacity' => 30,
    ]);

    $itCourse = Course::create([
        'code' => 'IT101',
        'title' => 'IT Course',
        'description' => 'IT course',
        'instructor_user_id' => $itFaculty->id,
        'capacity' => 30,
    ]);
    $otherCourse = Course::create([
        'code' => 'BUS101',
        'title' => 'Business Course',
        'description' => 'Business course',
        'instructor_user_id' => $otherFaculty->id,
        'capacity' => 30,
    ]);

    Schedule::create([
        'classroom_id' => $itRoom->id,
        'course_id' => $itCourse->id,
        'start_at' => now()->addHour(),
        'end_at' => now()->addHours(2),
        'status' => 'scheduled',
        'day_of_week' => now()->dayOfWeek,
        'enrolled' => 10,
    ]);
    Schedule::create([
        'classroom_id' => $otherRoom->id,
        'course_id' => $otherCourse->id,
        'start_at' => now()->addHour(),
        'end_at' => now()->addHours(2),
        'status' => 'scheduled',
        'day_of_week' => now()->dayOfWeek,
        'enrolled' => 10,
    ]);

    getJson('/api/v1/map/buildings')
        ->assertOk()
        ->assertJsonPath('data.0.building', 'IT Building')
        ->assertJsonMissing(['building' => 'Business Building']);

    getJson("/api/v1/map/rooms/{$otherRoom->id}/status")
        ->assertNotFound();
});

it('does not expose personal or internal schedule details on public map endpoints', function () {
    $faculty = createFacultyUser(['name' => 'Private Faculty']);
    $room = Classroom::create([
        'name' => 'Public Map Room',
        'building' => 'IT Building',
        'capacity' => 30,
    ]);
    $course = Course::create([
        'code' => 'IT202',
        'title' => 'Private Course',
        'description' => 'Private course',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);

    Schedule::create([
        'classroom_id' => $room->id,
        'course_id' => $course->id,
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
        'status' => 'ongoing',
        'day_of_week' => now()->dayOfWeek,
        'enrolled' => 10,
    ]);
    Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $faculty->id,
        'start_at' => now()->addHours(2),
        'end_at' => now()->addHours(3),
        'status' => 'approved',
        'notes' => 'Private reservation note',
    ]);

    getJson("/api/v1/map/rooms/{$room->id}/fixed-schedules")
        ->assertOk()
        ->assertJsonMissingPath('data.schedules.0.course')
        ->assertJsonMissingPath('data.schedules.0.instructor')
        ->assertJsonMissingPath('data.reservations.0.reserved_by')
        ->assertJsonMissingPath('data.reservations.0.notes')
        ->assertJsonMissingPath('data.reservations.0.is_mine');

    getJson("/api/v1/map/rooms/{$room->id}/status")
        ->assertOk()
        ->assertJsonMissingPath('data.current.course')
        ->assertJsonMissingPath('data.current.instructor')
        ->assertJsonMissingPath('data.current.reserved_by');
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
    $course = Course::create([
        'code' => 'IT-HOURS',
        'title' => 'IT Hours',
        'description' => 'Scope fixture',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);
    Schedule::create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-10 09:00:00',
        'end_at' => '2026-04-10 10:00:00',
        'status' => 'cancelled',
        'day_of_week' => 5,
        'enrolled' => 0,
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
    $course = Course::create([
        'code' => 'IT-SENSOR',
        'title' => 'IT Sensor',
        'description' => 'Scope fixture',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);
    Schedule::create([
        'classroom_id' => $classroom->id,
        'course_id' => $course->id,
        'start_at' => '2026-04-10 09:00:00',
        'end_at' => '2026-04-10 10:00:00',
        'status' => 'cancelled',
        'day_of_week' => 5,
        'enrolled' => 0,
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
