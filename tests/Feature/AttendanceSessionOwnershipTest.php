<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('only allows faculty to create ad-hoc attendance sessions for their own courses', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $otherFaculty = User::factory()->create(['role' => 'faculty']);
    $otherCourse = Course::create([
        'code' => 'OTHER101',
        'title' => 'Other Faculty Course',
        'instructor_user_id' => $otherFaculty->id,
        'capacity' => 30,
    ]);
    $payload = ['session_date' => today()->toDateString()];

    $this->actingAs($faculty)
        ->from(route('faculty.attendance'))
        ->post(route('faculty.attendance.store'), $payload + ['course_id' => $otherCourse->id])
        ->assertInvalid(['course_id']);

    expect(AttendanceSession::query()->where('created_by', $faculty->id)->exists())->toBeFalse();
});

it('prevents faculty from accessing or changing another faculty member attendance session', function () {
    $owner = User::factory()->create(['role' => 'faculty']);
    $otherFaculty = User::factory()->create(['role' => 'faculty']);
    $session = AttendanceSession::create([
        'date' => today()->toDateString(),
        'started_at' => now()->format('H:i:s'),
        'status' => 'open',
        'created_by' => $owner->id,
        'token' => 'OWNER-SESSION-TOKEN',
    ]);
    $existingRecord = AttendanceRecord::create([
        'attendance_session_id' => $session->id,
        'student_name' => 'Enrolled Student',
        'student_id' => 'STU-10001',
        'student_id_number' => 'STU-10001',
        'status' => 'present',
        'present' => true,
    ]);

    $this->actingAs($otherFaculty)
        ->get(route('faculty.attendance.qr', $session->id))
        ->assertRedirect(route('faculty.attendance'));

    $this->get(route('faculty.attendance.export', $session->id))
        ->assertRedirect(route('faculty.attendance'));

    $this->from(route('faculty.attendance'))
        ->post(route('faculty.attendance.record', $session->id), [
            'student_name' => 'Unauthorized Student',
            'present' => '1',
        ])
        ->assertRedirect(route('faculty.attendance'));

    $this->postJson(route('faculty.attendance.record.bulk', $session->id), [
        'records' => [[
            'student_name' => 'Replacement Student',
            'student_id_number' => 'STU-20002',
            'status' => 'present',
        ]],
    ])->assertNotFound();

    expect(AttendanceRecord::query()->where('attendance_session_id', $session->id)->count())->toBe(1);
    $this->assertDatabaseHas('attendance_records', ['id' => $existingRecord->id]);
    $this->assertDatabaseMissing('attendance_records', ['student_name' => 'Unauthorized Student']);
    $this->assertDatabaseMissing('attendance_records', ['student_name' => 'Replacement Student']);
});

it('keeps QR display and export available to the faculty member who owns the session', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $session = AttendanceSession::create([
        'date' => today()->toDateString(),
        'started_at' => now()->format('H:i:s'),
        'status' => 'open',
        'created_by' => $faculty->id,
        'token' => 'FACULTY-SESSION-TOKEN',
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.attendance.qr', $session->id))
        ->assertSuccessful();

    $this->get(route('faculty.attendance.export', $session->id))
        ->assertSuccessful();
});

it('renders and uses the registered attendance save route', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $session = AttendanceSession::create([
        'date' => today()->toDateString(),
        'started_at' => now()->format('H:i:s'),
        'status' => 'open',
        'created_by' => $faculty->id,
        'token' => 'SAVE-SESSION-TOKEN',
    ]);
    $saveUrl = route('faculty.attendance.record.bulk', $session->id);
    $closeUrl = route('faculty.attendance.close', $session->id);

    $response = $this->actingAs($faculty)
        ->get(route('faculty.attendance.session', $session->id))
        ->assertSuccessful();
    $renderedHtml = str_replace('\\/', '/', $response->getContent());

    expect($renderedHtml)
        ->toContain($saveUrl)
        ->toContain($closeUrl);

    $this->postJson($saveUrl, [
        'records' => [[
            'student_name' => 'Saved Student',
            'student_id_number' => 'STU-30003',
            'status' => 'present',
            'time_in' => now()->format('H:i:s'),
        ]],
    ])->assertSuccessful()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('attendance_records', [
        'attendance_session_id' => $session->id,
        'student_name' => 'Saved Student',
        'student_id_number' => 'STU-30003',
    ]);

    $this->postJson($closeUrl)
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($session->fresh()->status)->toBe('closed');
});

it('preserves existing attendance when a bulk roster payload is empty or malformed', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $session = AttendanceSession::create([
        'date' => today()->toDateString(),
        'started_at' => now()->format('H:i:s'),
        'status' => 'open',
        'created_by' => $faculty->id,
        'token' => 'VALIDATION-SESSION-TOKEN',
    ]);
    $existingRecord = AttendanceRecord::create([
        'attendance_session_id' => $session->id,
        'student_name' => 'Existing Student',
        'student_id_number' => 'STU-40004',
        'status' => 'present',
        'present' => true,
    ]);
    $saveUrl = route('faculty.attendance.record.bulk', $session->id);
    $invalidPayloads = [
        ['records' => []],
        ['records' => [['student_name' => '', 'status' => 'present']]],
        ['records' => [['student_name' => 'Invalid Status', 'status' => 'unknown']]],
    ];

    $this->actingAs($faculty);

    foreach ($invalidPayloads as $payload) {
        $this->postJson($saveUrl, $payload)->assertUnprocessable();
        $this->assertDatabaseHas('attendance_records', ['id' => $existingRecord->id]);
    }

    expect(AttendanceRecord::query()->where('attendance_session_id', $session->id)->count())->toBe(1);
});
