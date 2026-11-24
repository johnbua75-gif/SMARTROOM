<?php

use App\Mail\TemporaryPasswordMail;
use App\Models\AdminAuditLog;
use App\Models\Classroom;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('shows the system overview and administrator access to super admins', function () {
    $superAdmin = User::factory()->create([
        'name' => 'System Owner',
        'role' => 'super_admin',
        'status' => 'active',
    ]);
    User::factory()->create([
        'name' => 'Room Admin',
        'role' => 'admin',
        'status' => 'active',
    ]);
    User::factory()->create(['role' => 'student', 'status' => 'active']);
    User::factory()->create(['role' => 'student', 'status' => 'ACTIVE']);
    User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    User::factory()->create(['role' => 'service', 'status' => 'active']);

    $availableRoom = Classroom::query()->create([
        'name' => 'Available Room One',
        'building' => 'Main Building',
        'capacity' => 20,
        'status' => 'available',
    ]);
    Classroom::query()->create([
        'name' => 'Available Room Two',
        'building' => 'Main Building',
        'capacity' => 20,
        'status' => 'available',
    ]);
    Classroom::query()->create([
        'name' => 'Maintenance Room',
        'building' => 'Main Building',
        'capacity' => 20,
        'status' => 'maintenance',
    ]);
    Device::query()->create([
        'name' => 'Online Dashboard Device',
        'classroom_id' => $availableRoom->id,
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(2),
    ]);
    Device::query()->create([
        'name' => 'Awaiting Dashboard Device',
        'classroom_id' => $availableRoom->id,
        'status' => 'active',
    ]);
    Device::query()->create([
        'name' => 'Offline Dashboard Device',
        'classroom_id' => $availableRoom->id,
        'status' => 'active',
        'last_seen_at' => now()->subMinutes(10),
    ]);
    Device::query()->create([
        'name' => 'Disabled Dashboard Device',
        'classroom_id' => $availableRoom->id,
        'status' => 'inactive',
        'last_seen_at' => now()->subMinutes(2),
    ]);

    AdminAuditLog::query()->create([
        'actor_user_id' => $superAdmin->id,
        'target_user_id' => null,
        'action' => 'user.role_changed',
        'metadata' => ['target_name' => 'Room Admin'],
    ]);

    $this->actingAs($superAdmin)
        ->get(route('super-admin.dashboard'))
        ->assertOk()
        ->assertSee('System Overview')
        ->assertSee('Administrator access')
        ->assertSee('Room Admin')
        ->assertSee('Recent account changes')
        ->assertSee('Changed an account role')
        ->assertSee('User & Admin Access')
        ->assertSee('Room Management')
        ->assertSee('6 active accounts')
        ->assertSee('2 Available')
        ->assertSee('1 Maintenance')
        ->assertSee('Devices online now')
        ->assertSee('3 enabled of 4 configured')
        ->assertSee('1 awaiting')
        ->assertSee('1 offline')
        ->assertSee('1 disabled')
        ->assertSee('Accounts by role')
        ->assertSee('Students')
        ->assertSee('Service accounts')
        ->assertDontSee('Recent door activity')
        ->assertDontSee('Schedule')
        ->assertDontSee('SmartLocking')
        ->assertDontSee('Announcements')
        ->assertDontSee('Faculty Reports');
});

it('limits super admins to user and room management and redirects them to their overview after login', function () {
    $superAdmin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.users'))
        ->assertOk();

    $this->get(route('admin.classrooms'))->assertOk();

    foreach ([
        'admin.dashboard',
        'admin.schedule',
        'smartlocking.index',
        'admin.notifications.create',
        'admin.instructor-attendance',
        'accessLogs',
    ] as $routeName) {
        $this->get(route($routeName))->assertForbidden();
    }

    auth()->logout();

    $this->post(route('auth.login.submit'), [
        'email' => $superAdmin->email,
        'password' => 'password',
    ])->assertRedirect(route('super-admin.dashboard'));
});

it('limits super admin API mutations to room management', function () {
    $superAdmin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson('/api/v1/classrooms', [
            'name' => 'Super Admin API Room',
            'building' => 'Main Building',
            'capacity' => 20,
            'access_mode' => 'manual',
        ])
        ->assertCreated();

    $this->postJson('/api/v1/courses', [
        'name' => 'Restricted Course',
        'code' => 'RST-101',
    ])->assertForbidden();
});

it('preserves regular admin access to admin-only modules', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.schedule'))
        ->assertOk();

    $this->get(route('admin.notifications.create'))->assertOk();
});

it('allows the existing admin to establish the first super admin only once', function () {
    Mail::fake();
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->post(route('admin.users.store'), [
            'first_name' => 'First',
            'last_name' => 'Owner',
            'email' => 'first.owner@example.test',
            'role' => 'super_admin',
        ])
        ->assertRedirect(route('admin.users'));

    $firstSuperAdmin = User::query()->where('email', 'first.owner@example.test')->firstOrFail();
    expect($firstSuperAdmin->role)->toBe('super_admin');
    expect(AdminAuditLog::query()->where('action', 'administrator.created')->value('actor_user_id'))
        ->toBe($admin->id);

    Mail::assertSent(TemporaryPasswordMail::class);

    $this->from(route('admin.users'))
        ->post(route('admin.users.store'), [
            'first_name' => 'Second',
            'last_name' => 'Owner',
            'email' => 'second.owner@example.test',
            'role' => 'super_admin',
        ])
        ->assertRedirect(route('admin.users'))
        ->assertSessionHasErrors('role');
});

it('denies regular admins access to super-admin pages and protected accounts', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
    $superAdmin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get(route('super-admin.dashboard'))
        ->assertForbidden();

    $this->get(route('admin.users.edit', $superAdmin))
        ->assertForbidden();

    $this->patch(route('admin.users.update', $superAdmin), [
        'role' => 'admin',
    ])->assertForbidden();

    $this->post(route('admin.users.reset-password', $superAdmin))
        ->assertForbidden();

    $this->from(route('admin.users'))
        ->post(route('admin.users.store'), [
            'first_name' => 'New',
            'last_name' => 'Admin',
            'email' => 'new.admin@example.test',
            'role' => 'admin',
        ])
        ->assertSessionHasErrors('role');
});

it('prevents a super admin from changing their own role or disabling their account', function () {
    $superAdmin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $this->actingAs($superAdmin)
        ->from(route('admin.users.edit', $superAdmin))
        ->patch(route('admin.users.update', $superAdmin), [
            'role' => 'admin',
        ])
        ->assertRedirect(route('admin.users.edit', $superAdmin))
        ->assertSessionHasErrors('status');

    expect($superAdmin->fresh()->role)->toBe('super_admin');
});

it('records administrator role, status, and password changes', function () {
    Mail::fake();
    $superAdmin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);
    $administrator = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
    $administrator->createToken('revoke-on-suspension');

    $this->actingAs($superAdmin)
        ->patch(route('admin.users.update', $administrator), [
            'role' => 'super_admin',
        ])
        ->assertRedirect();

    $this->patch(route('admin.users.update', $administrator), [
        'status' => 'suspended',
    ])->assertRedirect();

    expect($administrator->fresh()->tokens()->count())->toBe(0);

    $this->post(route('admin.users.reset-password', $administrator))
        ->assertRedirect(route('admin.users'));

    expect(AdminAuditLog::query()->where('target_user_id', $administrator->id)->pluck('action')->all())
        ->toContain('user.role_changed', 'user.status_changed', 'password.reset');
});
