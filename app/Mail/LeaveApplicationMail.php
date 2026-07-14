<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LeaveApplication $leave)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->leave->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.leave_application',
            with: ['body' => $this->leave->renderBody()],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
