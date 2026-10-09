<?php

use App\Models\AccessCard;
use App\Models\AccessLog;
use App\Models\Classroom;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function liveFeedUser(string $role): User
{
    return User::factory()->create([
        'role' => $role,
        'status' => 'active',
        'must_change_password' => false,
    ]);
}

function liveFeedRoom(): Classroom
{
    return Classroom::create([
        'name' => 'Live Feed Room',
        'building' => 'Main Building',
        'access_mode' => 'esp32',
    ]);
}

it('returns only new owned RFID notifications in ascending order without caching', function () {
    Carbon::setTestNow('2026-10-10 09:30:00');

    $facultyA = liveFeedUser('faculty');
    $facultyB = liveFeedUser('faculty');
    $room = liveFeedRoom();
    $cardA = AccessCard::create([
        'user_id' => $facultyA->id,
        'classroom_id' => $room->id,
        'card_number' => 'LIVE-CARD-A',
        'rfid_uid' => 'AA:BB:CC:A1',
        'status' => 'active',
    ]);
    $cardB = AccessCard::create([
        'user_id' => $facultyB->id,
        'classroom_id' => $room->id,
        'card_number' => 'LIVE-CARD-B',
        'rfid_uid' => 'AA:BB:CC:A2',
        'status' => 'active',
    ]);
    Reservation::create([
        'classroom_id' => $room->id,
        'user_id' => $facultyB->id,
        'start_at' => '2026-10-10 09:00:00',
        'end_at' => '2026-10-10 10:00:00',
        'status' => 'approved',
    ]);

    actingAs($facultyA)->getJson('/faculty-notifications/rfid')
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('latest_id', 0);
    auth()->forgetGuards();
    actingAs($facultyB)->getJson('/faculty-notifications/rfid')
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('latest_id', 0);
    auth()->forgetGuards();

    $service = liveFeedUser('service');
    $token = $service->createToken('door-test', [
        'access-logs:create',
        'reservations:check',
        'access-cards:read',
        'door:'.$room->id,
    ])->plainTextToken;

    foreach ([[$facultyA, $cardA, 'denied'], [$facultyB, $cardB, 'granted']] as [$owner, $card, $result]) {
        $this->withToken($token)
            ->postJson('/api/v1/access-logs', [
                'classroom_id' => $room->id,
                'user_id' => $owner->id,
                'access_card_id' => $card->id,
                'direction' => 'entry',
                'result' => $result,
                'reason' => $result === 'denied' ? 'No Schedule' : null,
                'accessed_at' => '2026-10-10T09:30:00+08:00',
                'metadata' => ['method' => 'RFID', 'rfid_uid' => $card->rfid_uid],
            ])
            ->assertCreated();
    }

    $denied = Notification::query()->where('user_id', $facultyA->id)->where('type', 'rfid_access_denied')->sole();
    $granted = Notification::query()->where('user_id', $facultyB->id)->where('type', 'rfid_access_granted')->sole();
    $laterDenied = Notification::create([
        'user_id' => $facultyA->id,
        'type' => 'rfid_access_denied',
        'title' => 'Access Denied',
        'body' => 'Second event',
        'data' => ['access_log_id' => 9999, 'reason' => 'Reservation time has expired'],
    ]);
    Notification::create([
        'user_id' => null,
        'type' => 'rfid_access_denied',
        'title' => 'Access Denied',
        'body' => 'Unowned event',
        'data' => [],
    ]);
    Notification::create([
        'user_id' => $facultyA->id,
        'type' => 'course_assignment',
        'title' => 'Course assigned',
        'body' => 'Not an RFID event',
        'data' => [],
    ]);

    auth()->forgetGuards();
    actingAs($facultyA)
        ->getJson('/faculty-notifications/rfid?since_id=0')
        ->assertOk()
        ->assertJsonPath('data.0.id', $denied->id)
        ->assertJsonPath('data.1.id', $laterDenied->id)
        ->assertJsonMissing(['id' => $granted->id])
        ->assertJsonMissing(['body' => 'Unowned event'])
        ->assertJsonMissing(['body' => 'Not an RFID event']);

    Notification::create([
        'user_id' => $facultyA->id,
        'type' => 'rfid_access_granted',
        'title' => 'Access Granted',
        'body' => 'Created after the first cursor response',
        'data' => ['access_log_id' => 10000],
    ]);
    $this->getJson('/faculty-notifications/rfid?since_id='.$laterDenied->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.body', 'Created after the first cursor response');

    auth()->forgetGuards();
    actingAs($facultyB)
        ->getJson('/faculty-notifications/rfid?since_id=0')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $granted->id);

    auth()->forgetGuards();
    actingAs($facultyA)
        ->getJson('/faculty-notifications/rfid?since_id=999999')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('returns new access logs to admins and forbids faculty and students', function () {
    $admin = liveFeedUser('admin');
    $faculty = liveFeedUser('faculty');
    $student = liveFeedUser('student');
    $room = liveFeedRoom();
    $first = AccessLog::create([
        'user_id' => $faculty->id,
        'classroom_id' => $room->id,
        'direction' => 'entry',
        'result' => 'granted',
        'accessed_at' => now(),
        'metadata' => ['method' => 'RFID'],
    ]);
    $second = AccessLog::create([
        'user_id' => $student->id,
        'classroom_id' => $room->id,
        'direction' => 'entry',
        'result' => 'denied',
        'reason' => 'Reservation status is pending',
        'accessed_at' => now(),
        'metadata' => ['method' => 'RFID'],
    ]);

    actingAs($admin)
        ->getJson('/admin/access-logs/live?since_id='.$first->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $second->id)
        ->assertJsonPath('data.0.user_name', $student->name)
        ->assertJsonPath('data.0.classroom_name', $room->name)
        ->assertJsonPath('data.0.reason', 'Reservation status is pending')
        ->assertJsonPath('data.0.result', 'denied');

    $this->get('/admin/accessLogs')->assertOk()->assertSee('adminLiveAccessFeed');
    $this->get('/smartlocking')->assertOk()->assertSee('adminLiveAccessFeed');

    auth()->forgetGuards();
    actingAs($faculty)->getJson('/admin/access-logs/live?since_id=0')->assertForbidden();
    auth()->forgetGuards();
    actingAs($student)->getJson('/admin/access-logs/live?since_id=0')->assertForbidden();
});

it('bootstraps only RFID notifications created after page load', function () {
    $faculty = liveFeedUser('faculty');
    Carbon::setTestNow('2026-10-10 09:29:59');
    $old = Notification::create([
        'user_id' => $faculty->id,
        'type' => 'rfid_access_granted',
        'title' => 'Access Granted',
        'body' => 'Already existed at page load',
        'data' => [],
    ]);

    Carbon::setTestNow('2026-10-10 09:30:01');
    $new = Notification::create([
        'user_id' => $faculty->id,
        'type' => 'rfid_access_denied',
        'title' => 'Access Denied',
        'body' => 'Created after page load',
        'data' => [],
    ]);

    actingAs($faculty)
        ->getJson('/faculty-notifications/rfid?loaded_at=2026-10-10T09:30:00%2B08:00')
        ->assertOk()
        ->assertJsonPath('data.0.id', $new->id)
        ->assertJsonMissing(['id' => $old->id])
        ->assertJsonPath('latest_id', $new->id);
});

it('keeps the notification cursor endpoint clear of the default web throttle and indexes its query', function () {
    $faculty = liveFeedUser('faculty');

    actingAs($faculty);
    for ($request = 0; $request < 45; $request++) {
        $this->getJson('/faculty-notifications/rfid?since_id=0')->assertOk();
    }

    $indexes = collect(Schema::getIndexes('notifications'));
    expect($indexes->contains(fn (array $index): bool => $index['columns'] === ['user_id', 'type', 'id']))
        ->toBeTrue();
});
