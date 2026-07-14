<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LateComing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notice_date',
        'english_name',
        'japanese_name',
        'reason',
        'minutes',
        'to_emails',
        'cc_emails',
        'subject',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'notice_date' => 'date',
            'minutes'     => 'integer',
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
        return "Inform Late Coming in office_{$this->english_name}";
    }

    /** Effective subject: the custom one if set, otherwise the generated default. */
    public function subjectLine(): string
    {
        return trim((string) $this->subject) !== '' ? $this->subject : $this->renderSubject();
    }

    /** Body of the late-coming notice (Japanese). */
    public function renderBody(): string
    {
        $time = $this->minutes . '分';

        return <<<TXT
        宛先各位

        お疲れ様です。{$this->japanese_name}です。
        {$this->reason}で{$time}程遅れて出社しました。

        よろしくお願いいたします。
        TXT;
    }

    /** Copyable notice body (the subject line is kept out of the body — it's the mail subject). */
    public function renderTemplate(): string
    {
        return $this->renderBody();
    }
}
