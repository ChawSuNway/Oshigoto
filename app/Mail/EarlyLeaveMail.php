<?php

namespace App\Mail;

use App\Models\EarlyLeave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EarlyLeaveMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EarlyLeave $early)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->early->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.early_leave',
            with: ['body' => $this->early->renderBody()],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
