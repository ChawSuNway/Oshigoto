<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HalfDayLeave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notice_date',
        'english_name',
        'japanese_name',
        'department_name',
        'reason',
        'leave_type',
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

    /** Default subject: 【DD-MM-YYYY】Request for Half Day Leave application of [English name]. */
    public function renderSubject(): string
    {
        $date = optional($this->notice_date)->format('d-m-Y');

        return "【{$date}】Request for Half Day Leave application of {$this->english_name}";
    }

    /** Effective subject: the custom one if set, otherwise the generated default. */
    public function subjectLine(): string
    {
        return trim((string) $this->subject) !== '' ? $this->subject : $this->renderSubject();
    }

    /** Body of the half-day-leave application (Japanese). */
    public function renderBody(): string
    {
        $date   = optional($this->notice_date)->format('d-m-Y');
        $clause = $this->reasonClause();
        $verb   = $this->leaveVerb();

        return <<<TXT
        宛先各位

        お疲れ様です。{$this->japanese_name}@{$this->department_name}です。

        {$clause}、【{$date}】本日「{$this->leave_type}半休」を{$verb}。

        以上、よろしくお願いいたします。
        TXT;
    }

    /**
     * The reason joined to its natural causal connector:
     *   noun (体調不良)        → 体調不良なので
     *   verb / i-adj (忙しい)   → 忙しいので
     *   already causal (〜のため) → 〜のため（そのまま）
     */
    public function reasonClause(): string
    {
        $reason = trim((string) $this->reason);

        if ($reason === '') {
            return 'ので';
        }

        if (preg_match('/(ため|から)$/u', $reason)) {
            return $reason;
        }

        // Plain-form verb / い-adjective endings connect to ので directly; otherwise it's a noun (needs な).
        if (preg_match('/(い|う|く|ぐ|す|つ|ぬ|ふ|ぶ|む|る|た|だ|ん)$/u', $reason)) {
            return $reason . 'ので';
        }

        return $reason . 'なので';
    }

    /**
     * Closing verb chosen to match the reason:
     *   health / illness reasons → 休ませていただきます
     *   everything else          → 取らせていただきます
     */
    public function leaveVerb(): string
    {
        $healthKeywords = ['体調', '病気', '発熱', '風邪', '通院', '入院', '頭痛', '腹痛', '不良', '看病', 'けが', '怪我', '治療', '診察', 'インフル', 'コロナ', '休養', '具合'];

        foreach ($healthKeywords as $keyword) {
            if (mb_strpos((string) $this->reason, $keyword) !== false) {
                return '休ませていただきます';
            }
        }

        return '取らせていただきます';
    }

    /** Copyable notice body (the subject line is kept out of the body — it's the mail subject). */
    public function renderTemplate(): string
    {
        return $this->renderBody();
    }
}
