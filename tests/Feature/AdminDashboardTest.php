<?php

use App\Models\Classroom;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function dashboardAdmin(): User
{
    return User::create([
        'name' => 'Dash Admin',
        'email' => 'dash.admin@example.com',
        'password' => Hash::make('Passw0rd!'),
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);
}

it('shows the admin dashboard overview instead of user management', function () {
    $room = Classroom::create(['name' => 'Room 15', 'building' => 'Building A', 'capacity' => 40, 'status' => 'available']);
    Classroom::create(['name' => 'Room 17', 'building' => 'Building A', 'capacity' => 30, 'status' => 'maintenance']);

    Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => dashboardAdmin()->id,
        'start_at' => now()->subMinutes(10),
        'end_at' => now()->addHour(),
        'status' => 'approved',
    ]);

    actingAs(User::where('email', 'dash.admin@example.com')->first())
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertSee('Rooms free now')
        ->assertSee('schedule', false)
        ->assertSee('Recent door activity')
        ->assertDontSee('Add User');
});

it('keeps user management available on its own route', function () {
    actingAs(dashboardAdmin())->get('/admin/users')->assertOk();
});

