<?php

namespace App\Mail;

use App\Models\LateComing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LateComingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LateComing $late)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->late->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.late_coming',
            with: ['body' => $this->late->renderBody()],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
