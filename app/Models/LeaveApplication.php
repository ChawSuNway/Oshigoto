<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'english_name',
        'japanese_name',
        'department_name',
        'reason',
        'from_date',
        'to_date',
        'to_emails',
        'cc_emails',
        'subject',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date'   => 'date',
            'to_emails' => 'array',
            'cc_emails' => 'array',
            'sent_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Plain date label for lists / headings:
     *   single day (from = to) → DD-MM-YYYY
     *   range (from ≠ to)      → DD-MM-YYYY ~ DD-MM-YYYY
     */
    public function dateLabel(): string
    {
        $from = optional($this->from_date)->format('d-m-Y');
        $to   = optional($this->to_date)->format('d-m-Y');

        if (! $to || $to === $from) {
            return (string) $from;
        }

        return "{$from} ~ {$to}";
    }

    /**
     * Bracketed date block used inside the subject/body:
     *   single day (from = to) → 【DD-MM-YYYY】
     *   range (from ≠ to)      → 【DD-MM-YYYY】から【DD-MM-YYYY】まで
     */
    public function dateBlock(): string
    {
        $from = optional($this->from_date)->format('d-m-Y');
        $to   = optional($this->to_date)->format('d-m-Y');

        if (! $to || $to === $from) {
            return "【{$from}】";
        }

        return "【{$from}】から【{$to}】まで";
    }

    /** Default subject: 【date】 Request for Leave application of [English name]. */
    public function renderSubject(): string
    {
        return "{$this->dateBlock()} Request for Leave application of {$this->english_name}";
    }

    /** Effective subject: the custom one if set, otherwise the generated default. */
    public function subjectLine(): string
    {
        return trim((string) $this->subject) !== '' ? $this->subject : $this->renderSubject();
    }

    /** Body of the leave application (Japanese). */
    public function renderBody(): string
    {
        $block = $this->dateBlock();

        return <<<TXT
        関係者各位

        お疲れ様です。{$this->japanese_name}@{$this->department_name}です。

        {$this->reason}、{$block}休みです。

        以上、よろしくお願いいたします。
        TXT;
    }

    /** Copyable notice body (the subject line is the mail subject, kept out of the body). */
    public function renderTemplate(): string
    {
        return $this->renderBody();
    }
}
