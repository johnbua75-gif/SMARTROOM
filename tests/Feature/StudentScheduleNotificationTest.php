<?php

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows campus-wide and personal notices but hides notices for other students', function () {
    $student = User::factory()->create(['role' => 'student']);
    $anotherStudent = User::factory()->create(['role' => 'student']);

    Notification::create([
        'type' => 'announcement',
        'title' => 'Campus-wide notice',
        'body' => 'The library closes early today.',
    ]);
    Notification::create([
        'type' => 'schedule',
        'title' => 'Your schedule changed',
        'body' => 'Your class now meets in Room 204.',
        'user_id' => $student->id,
    ]);
    Notification::create([
        'type' => 'announcement',
        'title' => 'Another student notice',
        'user_id' => $anotherStudent->id,
    ]);

    $this->actingAs($student)
        ->get(route('student.home'))
        ->assertSuccessful()
        ->assertSee('Campus-wide notice')
        ->assertSee('Your schedule changed')
        ->assertDontSee('Another student notice');
});

it('limits the campus updates panel to the six most recent notices', function () {
    $student = User::factory()->create(['role' => 'student']);

    foreach (range(1, 7) as $number) {
        Notification::create([
            'type' => 'announcement',
            'title' => 'Campus notice '.$number,
        ]);
    }

    $this->actingAs($student)
        ->get(route('student.home'))
        ->assertSuccessful()
        ->assertSee('Campus notice 7')
        ->assertDontSee('Campus notice 1');
});

it('shows a useful empty state when there are no campus updates', function () {
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->get(route('student.home'))
        ->assertSuccessful()
        ->assertSee('No campus updates yet.');
});

it('lets an admin publish a campus announcement that students can see', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($admin)
        ->post(route('admin.notifications.store'), [
            'title' => 'Class cancellation',
            'body' => 'The afternoon lecture is cancelled.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('notifications', [
        'type' => 'announcement',
        'title' => 'Class cancellation',
        'user_id' => null,
    ]);

    $this->actingAs($student)
        ->get(route('student.home'))
        ->assertSuccessful()
        ->assertSee('Class cancellation')
        ->assertSee('The afternoon lecture is cancelled.');
});

it('broadcasts user-specific notifications only to the users private channel', function () {
    $user = User::factory()->create();
    $notification = Notification::create([
        'type' => 'enrollment_status',
        'title' => 'Enrollment approved',
        'body' => 'Your course enrollment was approved.',
        'data' => ['student_email' => $user->email],
        'user_id' => $user->id,
    ]);

    $channels = (new NewNotification($notification))->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('private-notifications.user.'.$user->id);
});

it('keeps campus-wide announcements on the public notifications channel', function () {
    $notification = Notification::create([
        'type' => 'announcement',
        'title' => 'Campus-wide notice',
        'body' => 'The library closes early today.',
    ]);

    $channels = (new NewNotification($notification))->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(Channel::class)
        ->and($channels[0])->not->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('notifications');
});
