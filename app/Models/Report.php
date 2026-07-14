<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'manager_id',
        'report_date',
        'user_name',
        'manager_name',
        'cc',
        'work_in',
        'work_out',
        'total_hours',
        'cases',
        'tomorrow_plans',
        'problems',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'report_date'    => 'date',
            'cc'             => 'array',
            'cases'          => 'array',
            'tomorrow_plans' => 'array',
            'total_hours'    => 'decimal:2',
            'sent_at'        => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** Lunch break automatically deducted from the gross working span (hours). */
    public const LUNCH_HOURS = 1.0;

    /**
     * Number of gross worked hours above which the lunch break applies.
     * Shorter shifts don't take a full lunch, so nothing is deducted.
     */
    public const LUNCH_THRESHOLD_HOURS = 6.0;

    /**
     * Compute net working hours between work_in and work_out, automatically
     * deducting a 1-hour lunch break once the gross span exceeds the threshold.
     * Handles overnight shifts by adding a day when work_out precedes work_in.
     */
    public static function calculateHours(?string $workIn, ?string $workOut): float
    {
        if (! $workIn || ! $workOut) {
            return 0.0;
        }

        [$inH, $inM]   = array_map('intval', explode(':', $workIn));
        [$outH, $outM] = array_map('intval', explode(':', $workOut));

        $start = $inH * 60 + $inM;
        $end   = $outH * 60 + $outM;

        if ($end < $start) {
            $end += 24 * 60; // overnight shift
        }

        $grossHours = ($end - $start) / 60;

        // Deduct the lunch break only for a full working day; never go negative.
        $netHours = $grossHours > self::LUNCH_THRESHOLD_HOURS
            ? $grossHours - self::LUNCH_HOURS
            : $grossHours;

        return round(max($netHours, 0), 2);
    }

    /**
     * "09:00" trimmed from a stored "09:00:00" time string.
     */
    public function timeHm(?string $value): string
    {
        if (! $value) {
            return '';
        }

        return substr($value, 0, 5);
    }

    /**
     * Build the Japanese report body exactly matching the required template.
     * Used for both the on-screen preview and the outgoing e-mail so they never diverge.
     */
    public function renderTemplate(): string
    {
        $headerIndent = "    ・ ";   // task-component header line
        $subIndent    = "    \t・ "; // sub-component line, indented one tab deeper

        // Lines derived from the Case -> Task Component -> Sub Task Component tree.
        // Each task component is a header; its sub components are grouped beneath it.
        $treeWorkLines = [];
        $treeProgressLines = [];
        foreach ($this->cases ?? [] as $case) {
            foreach ($case['task_components'] ?? [] as $task) {
                $taskName = trim((string) ($task['name'] ?? ''));
                if ($taskName === '') {
                    continue;
                }

                $subs = collect($task['sub_components'] ?? [])
                    ->filter(fn ($s) => trim((string) ($s['name'] ?? '')) !== '');

                // Work section: task header, then one indented line per sub component.
                $treeWorkLines[] = $headerIndent . $taskName;
                foreach ($subs as $sub) {
                    $treeWorkLines[] = $subIndent . trim((string) ($sub['name'] ?? ''));
                }

                // Progress section: same header, then only the subs carrying a percent.
                $progressSubs = $subs->filter(fn ($s) => trim((string) ($s['percent'] ?? '')) !== '');
                if ($progressSubs->isNotEmpty()) {
                    $treeProgressLines[] = $headerIndent . $taskName;
                    foreach ($progressSubs as $sub) {
                        $treeProgressLines[] = $subIndent . trim((string) ($sub['name'] ?? ''))
                            . ' (' . trim((string) ($sub['percent'] ?? '')) . '%)';
                    }
                }
            }
        }

        $workItems = implode("\n", $treeWorkLines);
        $progressItems = implode("\n", $treeProgressLines);

        $tomorrowPlans = collect($this->tomorrow_plans ?? [])
            ->filter(fn ($t) => trim((string) $t) !== '')
            ->map(fn ($t) => $headerIndent . trim((string) $t))
            ->implode("\n");

        $problems = trim((string) $this->problems) !== '' ? trim((string) $this->problems) : 'なし';

        $date = $this->report_date->format('d-m-Y');
        $in   = $this->timeHm($this->work_in);
        $out  = $this->timeHm($this->work_out);

        return <<<TXT
        {$this->manager_name}さん
        お疲れ様です。
        {$this->user_name}です。
        本日（{$date}）作業に関して、報告致します。

        【本日の作業時間 : Today's Working Time】
        {$in} ~ {$out}

        【本日の作業内容 : Today's Work】
        {$workItems}

        【問題件 : Problems】
        {$problems}

        【作業進捗％ : Work progress%】
        {$progressItems}

        【明日予定 : Tomorrow Plan】
        {$tomorrowPlans}

        以上です。
        宜しくお願いします。
        TXT;
    }

    /**
     * Alternative "TKD" progress-report layout, built from the same report data.
     * Sub components carry their percent inline; problems fall back to なし.
     */
    public function renderTkdTemplate(): string
    {
        $headerIndent = "    ・ ";   // task-component header line
        $subIndent    = "    \t・ "; // sub-component line, indented one tab deeper

        // Work content: task header, then each sub with an optional inline percent.
        $workLines = [];
        foreach ($this->cases ?? [] as $case) {
            foreach ($case['task_components'] ?? [] as $task) {
                $taskName = trim((string) ($task['name'] ?? ''));
                if ($taskName === '') {
                    continue;
                }

                $workLines[] = $headerIndent . $taskName;
                foreach ($task['sub_components'] ?? [] as $sub) {
                    $subName = trim((string) ($sub['name'] ?? ''));
                    if ($subName === '') {
                        continue;
                    }
                    $percent = trim((string) ($sub['percent'] ?? ''));
                    $workLines[] = $subIndent . $subName . ($percent !== '' ? '(' . $percent . '%)' : '');
                }
            }
        }

        $workContent = implode("\n", $workLines);

        $tomorrow = collect($this->tomorrow_plans ?? [])
            ->filter(fn ($t) => trim((string) $t) !== '')
            ->map(fn ($t) => $headerIndent . trim((string) $t))
            ->implode("\n");

        $problems = trim((string) $this->problems) !== '' ? trim((string) $this->problems) : 'なし';
        $date     = $this->report_date->format('Y/m/d');

        return <<<TXT
        {$this->manager_name}さん、お疲れ様です。
        今日「{$date}」の進捗報告を行います。
        ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
        １.【進捗状況】
        進捗：(-)
        ---------------------------------------------
        ２.【本日の作業内容 】
        {$workContent}

        【明日の作業予定】
        {$tomorrow}

        ---------------------------------------------
        ３．問題点：
        {$problems}
        ４．対策 ：
        なし
        --------------------------------------------------------
        以上、宜しくお願いいたします。
        TXT;
    }
}
