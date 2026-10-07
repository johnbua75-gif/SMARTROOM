<?php

use App\Models\Notification;
use App\Models\User;
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
