<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Report $report)
    {
    }

    public function envelope(): Envelope
    {
        $date = $this->report->report_date->format('d-m-Y');

        return new Envelope(
            subject: "日報【{$date}】",
        );
    }

    public function content(): Content
    {
        // Plain-text mail whose body is the exact rendered template.
        return new Content(
            text: 'emails.daily_report',
            with: ['body' => $this->report->renderTemplate()],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
