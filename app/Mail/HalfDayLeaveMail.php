<?php

namespace App\Mail;

use App\Models\HalfDayLeave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HalfDayLeaveMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public HalfDayLeave $half)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->half->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.half_day_leave',
            with: ['body' => $this->half->renderBody()],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
