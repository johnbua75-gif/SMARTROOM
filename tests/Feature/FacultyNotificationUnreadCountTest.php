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

it('marks only the authenticated faculty members notification as read', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $anotherFaculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);
    $notification = Notification::query()->create([
        'user_id' => $faculty->id,
        'type' => 'announcement',
        'title' => 'Please read this notice',
    ]);
    $otherNotification = Notification::query()->create([
        'user_id' => $anotherFaculty->id,
        'type' => 'announcement',
        'title' => 'Another faculty notice',
    ]);

    $this->actingAs($faculty)
        ->post(route('faculty.notifications.read', $notification->id))
        ->assertRedirect(route('faculty.notifications'));

    expect($notification->fresh()->read_at)->not->toBeNull()
        ->and($otherNotification->fresh()->read_at)->toBeNull();
});

it('marks all and only the authenticated faculty members unread notifications as read', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $anotherFaculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);
    $ownNotification = Notification::query()->create([
        'user_id' => $faculty->id,
        'type' => 'announcement',
        'title' => 'My notice',
    ]);
    $otherNotification = Notification::query()->create([
        'user_id' => $anotherFaculty->id,
        'type' => 'announcement',
        'title' => 'Another faculty notice',
    ]);

    $this->actingAs($faculty)
        ->post(route('faculty.notifications.read-all'))
        ->assertRedirect(route('faculty.notifications'));

    expect($ownNotification->fresh()->read_at)->not->toBeNull()
        ->and($otherNotification->fresh()->read_at)->toBeNull();
});

it('does not let a faculty member mark another faculty members notification as read', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $anotherFaculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);
    $notification = Notification::query()->create([
        'user_id' => $anotherFaculty->id,
        'type' => 'announcement',
        'title' => 'Private notice',
    ]);

    $this->actingAs($faculty)
        ->post(route('faculty.notifications.read', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

it('shows mark-as-read actions for unread notifications', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    Notification::query()->create([
        'user_id' => $faculty->id,
        'type' => 'announcement',
        'title' => 'Unread notice',
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.notifications'))
        ->assertSuccessful()
        ->assertSee('Mark as read')
        ->assertSee('Mark all as read');
});
