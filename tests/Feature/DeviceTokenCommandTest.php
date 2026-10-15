<?php

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

function createDeviceTokenCommandFixture(string $email = 'device-api@example.test'): User
{
    Classroom::create([
        'name' => 'Door 40',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);

    return User::factory()->create([
        'email' => $email,
        'role' => 'service',
        'status' => 'active',
    ]);
}

it('creates a Sanctum token with the requested classroom abilities and prints it once', function () {
    $user = createDeviceTokenCommandFixture();
    $classroom = Classroom::query()->firstOrFail();

    $this->artisan('device:token', [
        'email' => $user->email,
        'classroom_id' => $classroom->id,
    ])
        ->expectsOutputToContain('|')
        ->assertSuccessful();

    $token = PersonalAccessToken::query()->where('name', 'door-'.$classroom->id)->firstOrFail();

    expect($token->abilities)->toBe([
        'access-cards:read',
        'reservations:check',
        'access-logs:create',
        'device:heartbeat',
        'door:'.$classroom->id,
    ]);
});

it('does not issue a token to a non-service or inactive user', function () {
    Classroom::create([
        'name' => 'Door 40',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
    $user = User::factory()->create([
        'email' => 'faculty@example.test',
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $this->artisan('device:token', [
        'email' => $user->email,
        'classroom_id' => 1,
    ])
        ->expectsOutputToContain('existing active user with the service role')
        ->assertFailed();

    expect($user->tokens()->count())->toBe(0);
});

it('does not issue duplicate tokens with the same door name', function () {
    $user = createDeviceTokenCommandFixture();
    $user->createToken('door-1', ['existing']);

    $this->artisan('device:token', [
        'email' => $user->email,
        'classroom_id' => 1,
    ])
        ->expectsOutputToContain('already exists')
        ->assertFailed();

    expect($user->tokens()->count())->toBe(1);
});
