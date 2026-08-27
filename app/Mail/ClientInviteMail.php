<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You have been invited to Cloudline Billing');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.client-invite',
            with: [
                'user' => $this->user,
                'url' => $this->url,
            ],
        );
    }
}
