<?php

use App\Events\NewNotification;
use App\Models\Course;
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

it('renders the admin notification composer with shared navigation and accessible fields', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $recipient = User::factory()->create([
        'name' => 'Notification Recipient',
        'email' => 'notification-recipient@example.com',
        'role' => 'faculty',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.notifications.create'))
        ->assertSuccessful()
        ->assertSee('Create Notification')
        ->assertSee('Administrator navigation')
        ->assertSee('notification-form-panel', false)
        ->assertSee('notificationTitle', false)
        ->assertSee('notificationUserId', false)
        ->assertSee('value="'.$recipient->id.'"', false)
        ->assertSee('Notification Recipient')
        ->assertSee('Send to user')
        ->assertSee('Sign Out');
});

it('sends an admin announcement to the selected user only', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $recipient = User::factory()->create(['role' => 'faculty']);

    $this->actingAs($admin)
        ->post(route('admin.notifications.store'), [
            'title' => 'Targeted update',
            'body' => 'For one recipient.',
            'user_id' => $recipient->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('notifications', [
        'type' => 'announcement',
        'title' => 'Targeted update',
        'user_id' => $recipient->id,
    ]);
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

it('notifies the assigned faculty when a course is assigned or reassigned', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $faculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $replacementFaculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    $course = Course::create([
        'code' => 'ASSIGNMENT-LIVE-101',
        'title' => 'Assignment Notification Test',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.courses.update', $course), ['instructor_user_id' => $faculty->id])
        ->assertRedirect();

    $firstNotification = Notification::query()
        ->where('type', 'course_assignment')
        ->where('user_id', $faculty->id)
        ->firstOrFail();

    expect($firstNotification->data)->toMatchArray([
        'course_id' => $course->id,
        'course_code' => $course->code,
    ]);

    $this->put(route('admin.courses.update', $course), ['title' => 'Updated Course Title'])
        ->assertRedirect();
    expect(Notification::query()->where('type', 'course_assignment')->count())->toBe(1);

    $this->put(route('admin.courses.update', $course), ['instructor_user_id' => $replacementFaculty->id])
        ->assertRedirect();

    expect(Notification::query()->where('type', 'course_assignment')->where('user_id', $replacementFaculty->id)->count())->toBe(1);

    $this->actingAs($replacementFaculty)
        ->get(route('faculty.notifications'))
        ->assertSuccessful()
        ->assertSee('ASSIGNMENT-LIVE-101')
        ->assertSee('Updated Course Title')
        ->assertSee(route('faculty.notifications.data'), false)
        ->assertSee('setInterval', false);
});

it('shows faculty notification counts and responsive navigation', function () {
    $faculty = User::factory()->create(['role' => 'faculty', 'status' => 'active']);
    Notification::create([
        'type' => 'course_assignment',
        'title' => 'Subject assigned to you',
        'body' => 'You are assigned to teach CS101.',
        'user_id' => $faculty->id,
    ]);
    Notification::create([
        'type' => 'schedule',
        'title' => 'Schedule updated',
        'body' => 'Your schedule was updated.',
        'read_at' => now(),
        'user_id' => $faculty->id,
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.notifications'))
        ->assertSuccessful()
        ->assertSee('Faculty navigation')
        ->assertSee('aria-label="1 unread notifications"', false)
        ->assertSee('aria-label="2 total notifications"', false)
        ->assertSee('Course assignment')
        ->assertSee('notifications-mobile-menu', false);
});
