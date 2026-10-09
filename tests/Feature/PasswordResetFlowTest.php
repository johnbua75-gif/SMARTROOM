<?php

use App\Mail\TemporaryPasswordMail;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;

uses(RefreshDatabase::class);

it('renders the password reset request page', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Reset your password');
});

it('sends a reset link notification without contacting an external mail service', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->from(route('password.request'))
        ->post(route('password.email'), [
            'email' => $user->email,
        ])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('sends a temporary password email when an admin creates a user', function () {
    Mail::fake();

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'first_name' => 'Test',
            'last_name' => 'Faculty',
            'email' => 'new-faculty@example.test',
            'department' => 'Science',
            'role' => 'faculty',
        ])
        ->assertRedirect(route('admin.users'))
        ->assertSessionHas('status');

    Mail::assertSent(TemporaryPasswordMail::class, function (TemporaryPasswordMail $mail): bool {
        return $mail->hasTo('new-faculty@example.test');
    });
});

it('registers the Brevo HTTP API transport without sending an email', function () {
    config([
        'mail.mailers.brevo.api_key' => 'test-api-key',
    ]);

    Mail::purge('brevo');

    expect(Mail::mailer('brevo')->getSymfonyTransport())
        ->toBeInstanceOf(BrevoApiTransport::class);
});
