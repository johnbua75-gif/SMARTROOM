<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemporaryPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $tempPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your SmartRoom Temporary Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.temporary_password',
            with: [
                'name' => $this->name,
                'email' => $this->email,
                'tempPassword' => $this->tempPassword,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
