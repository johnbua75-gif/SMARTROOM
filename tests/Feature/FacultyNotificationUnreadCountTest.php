<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only the authenticated faculty members unread notification count', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $anotherFaculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    Notification::query()->create([
        'user_id' => $faculty->id,
        'type' => 'announcement',
        'title' => 'Unread notice one',
    ]);
    Notification::query()->create([
        'user_id' => $faculty->id,
        'type' => 'rfid_access_denied',
        'title' => 'Unread notice two',
    ]);
    Notification::query()->create([
        'user_id' => $faculty->id,
        'type' => 'announcement',
        'title' => 'Already read notice',
        'read_at' => now(),
    ]);
    Notification::query()->create([
        'user_id' => $anotherFaculty->id,
        'type' => 'announcement',
        'title' => 'Another faculty notice',
    ]);
    Notification::query()->create([
        'type' => 'announcement',
        'title' => 'Global notice',
    ]);

    $this->actingAs($faculty)
        ->getJson(route('faculty.notifications.unread-count'))
        ->assertSuccessful()
        ->assertJsonPath('count', 2);
});

it('requires an authenticated faculty member to access the unread count', function () {
    $this->getJson(route('faculty.notifications.unread-count'))
        ->assertUnauthorized();

    $student = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
    ]);

    $this->actingAs($student)
        ->getJson(route('faculty.notifications.unread-count'))
        ->assertForbidden();
});
