<?php

use App\Models\AccessCard;
use App\Models\AccessLog;
use App\Models\Classroom;
use App\Models\Device;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createDoorTestClassroom(int $id): Classroom
{
    DB::table('classrooms')->insert([
        'id' => $id,
        'name' => 'Door '.$id,
        'building' => 'Main Building',
        'access_mode' => 'esp32',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Classroom::query()->findOrFail($id);
}

function createDoorTestToken(User $user, array $abilities): string
{
    return $user->createToken('device-test', $abilities)->plainTextToken;
}

it('returns normalized active RFID matches in the ESP32 response shape', function () {
    $classroom = createDoorTestClassroom(40);
    $user = User::factory()->create(['status' => 'active']);
    $activeCardId = DB::table('access_cards')->insertGetId([
        'user_id' => $user->id,
        'classroom_id' => $classroom->id,
        'card_number' => 'DOOR-40-CARD',
        'rfid_uid' => 'rFiD_AA - BB : CC DD',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('access_cards')->insert([
        'user_id' => $user->id,
        'classroom_id' => $classroom->id,
        'card_number' => 'DOOR-40-INACTIVE',
        'rfid_uid' => 'AA:BB:CC:DE',
        'status' => 'inactive',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['access-cards:read', 'door:40']);

    $this->withToken($token)
        ->getJson('/api/v1/access-cards?rfid_uid=AA%3ABB%3ACC%3ADD')
        ->assertOk()
        ->assertExactJson([
            'data' => [[
                'id' => $activeCardId,
                'user_id' => $user->id,
                'rfid_uid' => 'rFiD_AA - BB : CC DD',
                'status' => 'active',
            ]],
        ]);

    $this->withToken($token)
        ->getJson('/api/v1/access-cards?rfid_uid=00%3A00%3A00%3A00')
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

it('returns JSON authentication failures and requires endpoint abilities', function () {
    $this->get('/api/v1/access-cards?rfid_uid=AA%3ABB%3ACC%3ADD')
        ->assertUnauthorized()
        ->assertJsonStructure(['message']);

    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['door:40']);

    $this->withToken($token)
        ->getJson('/api/v1/access-cards?rfid_uid=AA%3ABB%3ACC%3ADD')
        ->assertForbidden();
});

it('throttles requests per bearer token without a 60-request shared IP ceiling', function () {
    createDoorTestClassroom(40);
    createDoorTestClassroom(41);
    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $door40Token = createDoorTestToken($service, ['access-cards:read', 'door:40']);
    $door41Token = createDoorTestToken($service, ['access-cards:read', 'door:41']);

    foreach ([$door40Token, $door41Token] as $token) {
        for ($requestNumber = 0; $requestNumber < 61; $requestNumber++) {
            $this->withToken($token)
                ->getJson('/api/v1/access-cards?rfid_uid=AA%3ABB%3ACC%3ADD')
                ->assertOk();
        }
    }
});

it('only grants a reservation for the matching classroom at Manila time', function () {
    expect(config('app.timezone'))->toBe('Asia/Manila');

    $this->travelTo(Carbon::parse('2026-10-09T14:30:00+08:00'));
    $room40 = createDoorTestClassroom(40);
    createDoorTestClassroom(41);
    $user = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $card = AccessCard::create([
        'user_id' => $user->id,
        'classroom_id' => $room40->id,
        'card_number' => 'DOOR-40-RESERVATION',
        'rfid_uid' => 'RFID-AA-BB CC:DD',
        'status' => 'active',
    ]);
    Reservation::create([
        'classroom_id' => 40,
        'user_id' => $user->id,
        'start_at' => Carbon::parse('2026-10-09T14:00:00+08:00'),
        'end_at' => Carbon::parse('2026-10-09T15:00:00+08:00'),
        'status' => 'approved',
    ]);
    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['reservations:check', 'door:40', 'door:41']);

    $this->withToken($token)
        ->getJson('/api/v1/reservations/check?user_id='.$user->id.'&classroom_id=40&rfid_uid=AA%3ABB%3ACC%3ADD')
        ->assertOk()
        ->assertExactJson([
            'allowed' => true,
            'message' => 'Access granted',
            'reason' => '',
        ]);

    $this->withToken($token)
        ->getJson('/api/v1/reservations/check?user_id='.$user->id.'&classroom_id=41&rfid_uid='.$card->rfid_uid)
        ->assertOk()
        ->assertExactJson([
            'allowed' => false,
            'message' => 'No schedule',
            'reason' => 'No active reservation or class schedule is valid at this time.',
        ]);
});

it('rejects use of a door token for a different classroom', function () {
    createDoorTestClassroom(40);
    createDoorTestClassroom(41);
    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['reservations:check', 'door:40']);
    $user = User::factory()->create(['status' => 'active']);

    $this->withToken($token)
        ->getJson('/api/v1/reservations/check?user_id='.$user->id.'&classroom_id=41&rfid_uid=AA%3ABB%3ACC%3ADD')
        ->assertForbidden()
        ->assertJsonStructure(['message']);
});

it('keeps the device heartbeat available through the scoped Sanctum token', function () {
    createDoorTestClassroom(40);
    $device = Device::create([
        'name' => 'Door 40 heartbeat',
        'classroom_id' => 40,
        'status' => 'active',
        'credential_hash' => hash('sha256', 'test-device-credential'),
    ]);
    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['device:heartbeat', 'door:40']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/heartbeat', ['classroom_id' => 40])
        ->assertOk()
        ->assertExactJson([
            'message' => 'ok',
            'classroom_id' => 40,
            'server_time' => now()->toIso8601String(),
        ]);

    expect($response->status())->not->toBe(500);
    expect($device->fresh()->last_seen_at)->not->toBeNull();
});

it('rejects heartbeat requests for a classroom outside the token scope', function () {
    createDoorTestClassroom(40);
    createDoorTestClassroom(41);
    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['device:heartbeat', 'door:40']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/heartbeat', ['classroom_id' => 41])
        ->assertForbidden()
        ->assertJsonStructure(['message']);

    expect($response->status())->not->toBe(500);
});

it('validates the heartbeat classroom against existing classrooms', function () {
    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['device:heartbeat', 'door:999']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/heartbeat', ['classroom_id' => 999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['classroom_id']);

    expect($response->status())->not->toBe(500);
});

it('registers the named api rate limiter', function () {
    expect(RateLimiter::limiter('api'))->not->toBeNull();
});

it('requires authentication for heartbeat requests and returns JSON', function () {
    $response = $this->postJson('/api/v1/heartbeat', ['classroom_id' => 40])
        ->assertUnauthorized()
        ->assertJsonStructure(['message']);

    expect($response->status())->not->toBe(500);
});

it('returns a JSON not found response when a classroom has no active device', function () {
    createDoorTestClassroom(40);
    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['device:heartbeat', 'door:40']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/heartbeat', ['classroom_id' => 40])
        ->assertNotFound()
        ->assertJsonStructure(['message']);

    expect($response->status())->not->toBe(500);
});

it('forbids heartbeat when the token lacks the heartbeat ability', function () {
    createDoorTestClassroom(40);
    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['door:40']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/heartbeat', ['classroom_id' => 40])
        ->assertForbidden()
        ->assertJsonStructure(['message']);

    expect($response->status())->not->toBe(500);
});

it('does not return server errors for authenticated door API endpoints', function () {
    $classroom = createDoorTestClassroom(40);
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    Sanctum::actingAs($admin);

    $accessCardsResponse = $this->getJson('/api/v1/access-cards?rfid_uid=AA%3ABB%3ACC%3ADD');
    $reservationResponse = $this->getJson(
        '/api/v1/reservations/check?user_id='.$admin->id.'&classroom_id='.$classroom->id.'&rfid_uid=AA%3ABB%3ACC%3ADD'
    );
    $accessLogsResponse = $this->postJson('/api/v1/access-logs', [
        'classroom_id' => $classroom->id,
        'result' => 'denied',
        'direction' => 'entry',
        'reason' => 'Smoke test',
        'metadata' => ['method' => 'Fingerprint'],
    ]);

    expect($accessCardsResponse->status())->not->toBe(500)
        ->and($reservationResponse->status())->not->toBe(500)
        ->and($accessLogsResponse->status())->not->toBe(500);
});

it('stores anonymous fingerprint logs with an offset timestamp', function () {
    createDoorTestClassroom(40);
    createDoorTestClassroom(41);
    $this->travelTo(Carbon::parse('2026-10-09T14:30:00+08:00'));
    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['access-logs:create', 'door:40', 'door:41']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/access-logs', [
            'classroom_id' => 40,
            'result' => 'granted',
            'direction' => 'entry',
            'accessed_at' => '2026-10-09T14:30:00+08:00',
            'reason' => 'Fingerprint matched',
            'metadata' => ['method' => 'Fingerprint', 'fingerprint_id' => 1],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.classroom_id', 40)
        ->assertJsonPath('data.user_id', null)
        ->assertJsonPath('data.access_card_id', null)
        ->assertJsonPath('data.result', 'granted');

    $log = AccessLog::query()->findOrFail($response->json('data.id'));
    expect($log->user_id)->toBeNull()
        ->and($log->access_card_id)->toBeNull()
        ->and($log->accessed_at->format('Y-m-d H:i:sP'))->toBe('2026-10-09 14:30:00+08:00');

    $this->withToken($token)
        ->postJson('/api/v1/access-logs', [
            'classroom_id' => 41,
            'result' => 'denied',
            'direction' => 'entry',
            'metadata' => ['method' => 'Keypad'],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.classroom_id', 41)
        ->assertJsonPath('data.access_card_id', null)
        ->assertJsonPath('data.user_id', null);

    expect(AccessLog::query()->where('classroom_id', 41)->exists())->toBeTrue();
});

it('rejects unsupported access log results and timestamps without an offset', function () {
    createDoorTestClassroom(40);
    $service = User::factory()->create(['role' => 'service', 'status' => 'active']);
    $token = createDoorTestToken($service, ['access-logs:create', 'door:40']);

    $this->withToken($token)
        ->postJson('/api/v1/access-logs', [
            'classroom_id' => 40,
            'result' => 'timeout',
            'direction' => 'entry',
            'accessed_at' => '2026-10-09T14:30:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['result', 'accessed_at']);
});

it('normalizes RFID UIDs when new access cards are saved', function () {
    $user = User::factory()->create(['status' => 'active']);

    $card = AccessCard::create([
        'user_id' => $user->id,
        'card_number' => 'NORMALIZED-CARD',
        'rfid_uid' => 'rFiD_AA-BB CC:DD',
        'status' => 'active',
    ]);

    expect($card->fresh()->rfid_uid)->toBe('AA:BB:CC:DD');
});
