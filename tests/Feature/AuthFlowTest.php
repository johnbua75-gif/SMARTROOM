<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('can sign up a new user', function () {
    $response = post('/signup', [
        'firstName' => 'Jane',
        'lastName' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'Passw0rd!',
        'password_confirmation' => 'Passw0rd!',
        'terms' => 'on',
    ]);

    $response->assertRedirect(route('student.home'));

    assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);

    assertAuthenticated();
});

it('can sign in an existing user', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => Hash::make('Passw0rd!'),
        'must_change_password' => false,
    ]);

    $response = post('/login', [
        'email' => $user->email,
        'password' => 'Passw0rd!',
    ]);

    $response->assertRedirect(route('faculty.dashboard'));
    assertAuthenticatedAs($user);
});

it('redirects admin users to admin dashboard on login', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $response = post('/login', [
        'email' => $admin->email,
        'password' => 'Passw0rd!',
    ]);

    $response->assertRedirect(route('admin.schedule'));
    assertAuthenticatedAs($admin);
});

it('redirects mixed-case admin roles to admin dashboard on login', function () {
    $admin = User::create([
        'name' => 'Admin Mixed Case',
        'email' => 'admin.mixed@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'Admin',
        'must_change_password' => false,
    ]);

    $response = post('/login', [
        'email' => $admin->email,
        'password' => 'Passw0rd!',
    ]);

    $response->assertRedirect(route('admin.schedule'));
    assertAuthenticatedAs($admin);
});

it('does not redirect non-admin roles that contain admin text', function () {
    $facultyLike = User::create([
        'name' => 'Faculty Admin Assistant',
        'email' => 'faculty.admin.assistant@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'faculty_admin_assistant',
        'must_change_password' => false,
    ]);

    $response = post('/login', [
        'email' => $facultyLike->email,
        'password' => 'Passw0rd!',
    ]);

    $response->assertRedirect(route('faculty.dashboard'));
    assertAuthenticatedAs($facultyLike);
});

it('blocks faculty from admin pages', function () {
    $faculty = User::create([
        'name' => 'Faculty User',
        'email' => 'faculty@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'faculty',
        'must_change_password' => false,
    ]);

    actingAs($faculty)
        ->get('/dashboard')
        ->assertForbidden();
});

it('admin can reset a user password and force password change', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin.reset@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $user = User::create([
        'name' => 'Reset User',
        'email' => 'reset.user@example.com',
        'password' => Hash::make('OldPassword123!'),
        'role' => 'faculty',
        'must_change_password' => false,
    ]);

    actingAs($admin)
        ->post('/admin/users/'.$user->id.'/reset-password')
        ->assertRedirect('/admin/users');

    $user->refresh();

    expect($user->must_change_password)->toBeTrue();
    expect(Hash::check('OldPassword123!', $user->password))->toBeFalse();
    expect($user->password)->not->toBe('OldPassword123!');
});

it('blocks suspended users from logging in', function () {
    $user = User::create([
        'name' => 'Suspended User',
        'email' => 'suspended@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'faculty',
        'status' => 'suspended',
        'must_change_password' => false,
    ]);

    post('/login', [
        'email' => $user->email,
        'password' => 'Passw0rd!',
    ])->assertRedirect(route('auth.login'))
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('admin can deactivate and reactivate a user', function () {
    $admin = User::create([
        'name' => 'Status Admin',
        'email' => 'status.admin@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    $user = User::create([
        'name' => 'Status User',
        'email' => 'status.user@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    actingAs($admin)->patch('/admin/users/'.$user->id, [
        'status' => 'inactive',
    ])->assertRedirect();

    expect($user->refresh()->status)->toBe('inactive');

    actingAs($admin)->patch('/admin/users/'.$user->id, [
        'status' => 'active',
    ])->assertRedirect();

    expect($user->refresh()->status)->toBe('active');
});

it('prevents an admin from suspending their own account', function () {
    $admin = User::create([
        'name' => 'Self Lockout Admin',
        'email' => 'self.lockout@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    actingAs($admin)
        ->patch('/admin/users/'.$admin->id, ['status' => 'suspended'])
        ->assertSessionHasErrors('status');

    expect($admin->refresh()->status)->toBe('active');
});

it('removes faculty course assignments without deleting the faculty user', function () {
    $admin = User::create([
        'name' => 'Assignment Admin',
        'email' => 'assignment.admin@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    $faculty = User::create([
        'name' => 'Faculty To Remove',
        'email' => 'faculty.remove@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    $replacement = User::create([
        'name' => 'Replacement Faculty',
        'email' => 'replacement.faculty@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    $course = Course::create([
        'code' => 'CS101',
        'title' => 'Intro to Computing',
        'description' => 'Test course',
        'capacity' => 30,
        'instructor_user_id' => $faculty->id,
    ]);

    actingAs($admin)
        ->deleteJson('/admin/users/'.$faculty->id.'/reassign', [
            'replacement_user_id' => $replacement->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.reassigned_courses', 1);

    expect(User::query()->whereKey($faculty->id)->exists())->toBeTrue();
    expect($course->fresh()->instructor_user_id)->toBe($replacement->id);
});

it('shows faculty with assigned unscheduled subjects in admin schedule list', function () {
    $admin = User::create([
        'name' => 'Schedule Admin',
        'email' => 'schedule.admin@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    $faculty = User::create([
        'name' => 'Assigned Faculty',
        'email' => 'assigned.faculty@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'faculty',
        'department' => 'BSIT',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    Course::create([
        'code' => 'TEST101',
        'title' => 'Assigned Subject',
        'instructor_user_id' => $faculty->id,
        'capacity' => 30,
    ]);

    actingAs($admin);

    get('/admin/schedule')
        ->assertOk()
        ->assertSee('Assigned Faculty')
        ->assertSee('1 Subject(s)')
        ->assertSee('Assigned Subject');
});
