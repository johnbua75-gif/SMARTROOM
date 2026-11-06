<?php

use App\Models\AccessCard;
use App\Models\AccessLog;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('keeps a card active through its expiry date', function () {
    Carbon::setTestNow('2026-11-06 15:00:00');

    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    AccessCard::create([
        'user_id' => $faculty->id,
        'card_number' => 'EXPIRY-DAY-CARD',
        'rfid_uid' => 'A1:B2:C3:D4',
        'status' => 'active',
        'expires_at' => today()->toDateString(),
    ]);

    $response = $this->actingAs($faculty)->get(route('faculty.rfid.verification'));
    Carbon::setTestNow();

    $response->assertOk()
        ->assertSee('Active')
        ->assertSee('Expires today')
        ->assertDontSee('Expired');
});

it('shows the latest RFID access log as the card last seen time', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $classroom = Classroom::create([
        'name' => 'RFID Accuracy Room',
        'building' => 'Main Building',
    ]);
    $storedLastSeen = now()->subDays(3);
    $loggedLastSeen = now()->subHour();
    $card = AccessCard::create([
        'user_id' => $faculty->id,
        'classroom_id' => $classroom->id,
        'card_number' => 'LAST-SEEN-CARD',
        'rfid_uid' => 'A1:B2:C3:D4',
        'status' => 'active',
        'last_accessed_at' => $storedLastSeen,
    ]);
    AccessLog::create([
        'access_card_id' => $card->id,
        'classroom_id' => $classroom->id,
        'user_id' => $faculty->id,
        'direction' => 'entry',
        'result' => 'denied',
        'accessed_at' => $loggedLastSeen,
        'metadata' => ['method' => 'RFID'],
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.rfid.verification'))
        ->assertOk()
        ->assertSee($loggedLastSeen->format('M j, Y g:i A'))
        ->assertDontSee($storedLastSeen->format('M j, Y g:i A'));
});

it('shows scheduled rooms when the card has no card-specific room assignment', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'must_change_password' => false,
    ]);
    $course = Course::create([
        'code' => 'RFID-ROOM-101',
        'title' => 'RFID Room Assignment',
        'instructor_user_id' => $faculty->id,
    ]);
    $roomOne = Classroom::create([
        'name' => 'Scheduled Room 15',
        'building' => 'Main Building',
    ]);
    $roomTwo = Classroom::create([
        'name' => 'Scheduled Room 16',
        'building' => 'Main Building',
    ]);
    $cancelledRoom = Classroom::create([
        'name' => 'Cancelled Room',
        'building' => 'Main Building',
    ]);
    AccessCard::create([
        'user_id' => $faculty->id,
        'card_number' => 'SCHEDULED-ROOM-CARD',
        'rfid_uid' => 'A1:B2:C3:D4',
        'status' => 'active',
    ]);

    foreach ([$roomOne, $roomTwo] as $index => $classroom) {
        Schedule::create([
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'instructor_user_id' => $faculty->id,
            'start_at' => now()->addDays($index + 1)->setTime(9, 0),
            'end_at' => now()->addDays($index + 1)->setTime(10, 0),
            'status' => 'scheduled',
        ]);
    }

    Schedule::create([
        'classroom_id' => $cancelledRoom->id,
        'course_id' => $course->id,
        'instructor_user_id' => $faculty->id,
        'start_at' => now()->addDays(3)->setTime(9, 0),
        'end_at' => now()->addDays(3)->setTime(10, 0),
        'status' => 'cancelled',
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.rfid.verification'))
        ->assertOk()
        ->assertSee('Scheduled Room 15')
        ->assertSee('Scheduled Room 16')
        ->assertDontSee('Cancelled Room')
        ->assertDontSee('No room assigned');
});
