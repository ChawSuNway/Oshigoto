<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EarlyLeave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notice_date',
        'english_name',
        'japanese_name',
        'department_name',
        'reason',
        'leave_time',
        'to_emails',
        'cc_emails',
        'subject',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'notice_date' => 'date',
            'to_emails'   => 'array',
            'cc_emails'   => 'array',
            'sent_at'     => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Default subject generated from the English name. */
    public function renderSubject(): string
    {
        return "Request for Early Leave application_{$this->english_name}";
    }

    /** Effective subject: the custom one if set, otherwise the generated default. */
    public function subjectLine(): string
    {
        return trim((string) $this->subject) !== '' ? $this->subject : $this->renderSubject();
    }

    /** Body of the early-leave application (Japanese). */
    public function renderBody(): string
    {
        return <<<TXT
        関係者各位

        お疲れ様です。{$this->japanese_name}@{$this->department_name}です。

        今日は{$this->reason}、{$this->leave_time}時に早退させて頂きます。
        お忙しいところ恐縮ですが、宜しくお願い致します。

        以上です。
        TXT;
    }

    /** Copyable notice body (the subject line is kept out of the body — it's the mail subject). */
    public function renderTemplate(): string
    {
        return $this->renderBody();
    }
}
