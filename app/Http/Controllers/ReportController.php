<?php

namespace App\Http\Controllers;

use App\Mail\DailyReportMail;
use App\Models\Report;
use App\Models\SystemId;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    /**
     * List reports. Employees see their own; managers see reports sent to them.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $reports = $user->isManager()
            ? $user->receivedReports()->with('user')->latest('report_date')->paginate(15)
            : $user->reports()->with('manager')->latest('report_date')->paginate(15);

        return view('reports.index', compact('reports'));
    }

    public function create(Request $request)
    {
        return view('reports.create', [
            'report'   => new Report([
                'report_date'  => now()->toDateString(),
                'user_name'    => $request->user()->name,
                'manager_name' => optional($request->user()->manager)->name ?? '',
                'manager_id'   => $request->user()->manager_id,
                'work_in'      => '08:00',
                'work_out'     => '17:00',
            ]),
            'managers'  => $this->managers(),
            'systemIds' => $this->systemIdOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $report = $request->user()->reports()->create($data);

        return redirect()->route('reports.show', $report)
            ->with('status', 'Report saved. Preview it below, then send it to your manager.');
    }

    /**
     * Preview page — shows the rendered template and a send button.
     */
    public function show(Request $request, Report $report)
    {
        $this->authorizeView($request, $report);

        return view('reports.show', [
            'report'  => $report,
            'preview' => $report->renderTemplate(),
        ]);
    }

    public function edit(Request $request, Report $report)
    {
        $this->authorizeOwner($request, $report);

        return view('reports.edit', [
            'report'    => $report,
            'managers'  => $this->managers(),
            'systemIds' => $this->systemIdOptions(),
        ]);
    }

    public function update(Request $request, Report $report)
    {
        $this->authorizeOwner($request, $report);

        $report->update($this->validated($request));

        return redirect()->route('reports.show', $report)
            ->with('status', 'Report updated.');
    }

    public function destroy(Request $request, Report $report)
    {
        $this->authorizeOwner($request, $report);

        $report->delete();

        return redirect()->route('reports.index')->with('status', 'Report deleted.');
    }

    /**
     * Send the rendered template by e-mail to the assigned manager.
     */
    public function send(Request $request, Report $report)
    {
        $this->authorizeOwner($request, $report);

        $manager = $report->manager;

        if (! $manager || ! $manager->email) {
            return back()->with('error', 'No manager e-mail is set for this report. Edit the report and choose a manager.');
        }

        $cc = collect($report->cc ?? [])->filter()->values()->all();

        $mail = Mail::to($manager->email);
        if (! empty($cc)) {
            $mail->cc($cc);
        }
        $mail->send(new DailyReportMail($report));

        $report->update(['sent_at' => now()]);

        $ccNote = ! empty($cc) ? ' (cc: ' . implode(', ', $cc) . ')' : '';

        return redirect()->route('reports.show', $report)
            ->with('status', "Report e-mailed to {$manager->name} ({$manager->email}){$ccNote}.");
    }

    // ---------------------------------------------------------------------
    // Monthly export
    // ---------------------------------------------------------------------

    /**
     * Monthly report page: month picker + on-screen preview of the export grid.
     */
    public function monthly(Request $request)
    {
        [$start, $end, $label, $monthValue] = $this->monthRange($request);
        $reports = $this->reportsForMonth($request, $start, $end);

        return view('reports.monthly', [
            'rows'       => $this->buildExportRows($reports),
            'label'      => $label,
            'monthValue' => $monthValue,
            'count'      => $reports->count(),
        ]);
    }

    /**
     * Stream the selected month's reports as an .xlsx download.
     */
    public function monthlyExport(Request $request)
    {
        [$start, $end, $label, $monthValue] = $this->monthRange($request);
        $reports = $this->reportsForMonth($request, $start, $end);
        $rows = $this->buildExportRows($reports);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($monthValue, 0, 31));

        $headers = ['日付', 'システムID', '案件No', '時間外/電話対応', '時間（h）', '作業(サギョウ)内容(ナイヨウ)'];
        $sheet->fromArray($headers, null, 'A1');

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8EAF6');
        $sheet->getStyle('A1:F1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $rowNum = 2;
        foreach ($rows as $row) {
            // Case-level columns are written (and merged) only on the case's first row.
            if ($row['first']) {
                $sheet->setCellValueExplicit("A{$rowNum}", $row['date'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B{$rowNum}", $row['system_id'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$rowNum}", $row['case_no'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$rowNum}", $row['sst'], DataType::TYPE_STRING);
                if ($row['time_h'] !== '') {
                    $sheet->setCellValue("E{$rowNum}", (float) $row['time_h']);
                    $sheet->getStyle("E{$rowNum}")->getNumberFormat()->setFormatCode('0.00');
                }

                if ($row['rowspan'] > 1) {
                    $endRow = $rowNum + $row['rowspan'] - 1;
                    foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
                        $sheet->mergeCells("{$col}{$rowNum}:{$col}{$endRow}");
                    }
                }
            }

            $sheet->setCellValueExplicit("F{$rowNum}", $row['content'], DataType::TYPE_STRING);
            $sheet->getStyle("F{$rowNum}")->getAlignment()->setIndent($row['sub'] ? 2 : 0);
            $rowNum++;
        }

        $lastRow = max($rowNum - 1, 1);
        $sheet->getStyle("A1:F{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A2:E{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        foreach (['A' => 12, 'B' => 16, 'C' => 14, 'D' => 18, 'E' => 10, 'F' => 55] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $filename = "monthly_report_{$monthValue}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Resolve the requested ?month=YYYY-MM into [start, end, label, value]; defaults to current month. */
    protected function monthRange(Request $request): array
    {
        $monthValue = (string) $request->input('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $monthValue)) {
            $monthValue = now()->format('Y-m');
        }

        // Billing cycle: 26th of the previous month through the 25th of the selected month.
        // e.g. selecting June → 26 May – 25 June.
        $anchor = Carbon::parse($monthValue . '-01')->startOfMonth();
        $end    = (clone $anchor)->day(25)->endOfDay();
        $start  = (clone $anchor)->subMonthNoOverflow()->day(26)->startOfDay();

        $label = $start->format('j M') . ' – ' . $end->format('j M Y');

        return [$start, $end, $label, $anchor->format('Y-m')];
    }

    /** Reports the current user may see (own, or received as a manager) within the date range. */
    protected function reportsForMonth(Request $request, Carbon $start, Carbon $end)
    {
        $user = $request->user();

        $query = $user->isManager() ? $user->receivedReports() : $user->reports();

        return $query
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('report_date')
            ->get();
    }

    /**
     * Flatten reports -> cases -> task/sub components into export grid rows.
     * Case-level columns appear on the first line of each case; the work-content
     * column then continues across further lines for extra tasks and sub tasks.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildExportRows($reports): array
    {
        $rows = [];

        foreach ($reports as $report) {
            // e.g. "2026/7/3(金)" — no leading zeros, Japanese weekday.
            $date  = $report->report_date->locale('ja')->isoFormat('YYYY/M/D(ddd)');
            $cases = $report->cases ?? [];

            if (empty($cases)) {
                $rows[] = $this->exportRow($date, '', '', '', '', false, true, 1);
                continue;
            }

            foreach ($cases as $case) {
                $lines = [];
                foreach ($case['task_components'] ?? [] as $task) {
                    $taskName = trim((string) ($task['name'] ?? ''));
                    if ($taskName !== '') {
                        $lines[] = ['text' => $taskName, 'sub' => false];
                    }
                    foreach ($task['sub_components'] ?? [] as $sub) {
                        $subName = trim((string) ($sub['name'] ?? ''));
                        if ($subName !== '') {
                            $lines[] = ['text' => '・ ' . $subName, 'sub' => true];
                        }
                    }
                }
                if (empty($lines)) {
                    $lines[] = ['text' => '', 'sub' => false];
                }

                // Rows this case spans; case-level columns are merged across it.
                $span  = count($lines);
                $timeH = ($case['time_h'] ?? null) === null || $case['time_h'] === '' ? '' : (string) $case['time_h'];
                $first = array_shift($lines);

                $rows[] = $this->exportRow(
                    $date,
                    trim((string) ($case['system_id'] ?? '')),
                    trim((string) ($case['case_no'] ?? '')),
                    $timeH,
                    $first['text'],
                    $first['sub'],
                    true,
                    $span,
                );

                foreach ($lines as $line) {
                    $rows[] = $this->exportRow('', '', '', '', $line['text'], $line['sub'], false, 0);
                }
            }
        }

        return $rows;
    }

    /** Build one export grid row. $rowspan is the case block height (only meaningful on the first row). */
    protected function exportRow(string $date, string $systemId, string $caseNo, string $timeH, string $content, bool $sub, bool $first, int $rowspan = 1): array
    {
        return [
            'date'      => $date,
            'system_id' => $systemId,
            'case_no'   => $caseNo,
            'sst'       => '',
            'time_h'    => $timeH,
            'content'   => $content,
            'sub'       => $sub,
            'first'     => $first,
            'rowspan'   => $rowspan,
        ];
    }

    // ---------------------------------------------------------------------

    /**
     * Validate + normalize request data into a persistable array.
     */
    protected function validated(Request $request): array
    {
        // Accept CC as a comma / semicolon / whitespace separated list, normalise to an array.
        $ccList = collect(preg_split('/[,;\s]+/', (string) $request->input('cc', ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($e) => trim($e))
            ->filter()
            ->unique()
            ->values()
            ->all();
        // Pre-clean the nested / repeatable inputs so the "at least one" rules below
        // count only rows that actually contain data.
        $request->merge([
            'cc'             => $ccList,
            'cases'          => $this->cleanCases((array) $request->input('cases', [])),
            'tomorrow_plans' => collect($request->input('tomorrow_plans', []))
                ->filter(fn ($t) => trim((string) $t) !== '')
                ->values()
                ->all(),
        ]);

        $validated = $request->validate([
            'report_date'              => ['required', 'date'],
            'user_name'                => ['required', 'string', 'max:255'],
            'manager_id'               => ['required', Rule::exists('users', 'id')->where('role', 'manager')],
            'manager_name'             => ['required', 'string', 'max:255'],
            'cc'                       => ['required', 'array', 'min:1'],
            'cc.*'                     => ['email'],
            'work_in'                  => ['required', 'date_format:H:i'],
            'work_out'                 => ['required', 'date_format:H:i'],

            'cases'                                             => ['required', 'array', 'min:1'],
            'cases.*.system_id'                                 => ['required', 'string', 'max:255'],
            'cases.*.case_no'                                   => ['required', 'string', 'max:255'],
            'cases.*.time_h'                                    => ['required', 'numeric', 'min:0', 'max:24'],
            'cases.*.task_components'                           => ['required', 'array', 'min:1'],
            'cases.*.task_components.*.name'                    => ['required', 'string', 'max:255'],
            'cases.*.task_components.*.sub_components'          => ['required', 'array', 'min:1'],
            'cases.*.task_components.*.sub_components.*.name'   => ['required', 'string', 'max:255'],
            'cases.*.task_components.*.sub_components.*.percent' => ['required', 'integer', 'min:0', 'max:100'],

            'tomorrow_plans'           => ['required', 'array', 'min:1'],
            'tomorrow_plans.*'         => ['required', 'string', 'max:1000'],

            'problems'                 => ['nullable', 'string', 'max:2000'],
        ], [
            'cc.*.email'             => 'Each CC entry must be a valid e-mail address.',
            'cc.required'            => 'Please add at least one CC e-mail address.',
            'cc.min'                 => 'Please add at least one CC e-mail address.',
            'report_date.required'   => 'Please choose a date.',
            'user_name.required'     => 'Please enter your name.',
            'manager_id.required'    => 'Please select a manager.',
            'manager_id.exists'      => 'Please select a valid manager.',
            'manager_name.required'  => 'Please enter the manager name.',
            'work_in.required'       => 'Please enter the work-in time.',
            'work_in.date_format'    => 'Work-in time must be in HH:MM format.',
            'work_out.required'      => 'Please enter the work-out time.',
            'work_out.date_format'   => 'Work-out time must be in HH:MM format.',
            'cases.required'         => 'Please add at least one case.',
            'cases.min'              => 'Please add at least one case.',
            'cases.*.system_id.required' => 'Each case needs a System ID.',
            'cases.*.case_no.required'   => 'Each case needs a Case No.',
            'cases.*.time_h.required' => 'Each case needs a time (hours).',
            'cases.*.time_h.numeric' => 'Case time must be a number (hours).',
            'cases.*.time_h.max'     => 'Case time cannot exceed 24 hours.',
            'cases.*.task_components.*.sub_components.*.percent.required' => 'Each sub task component needs a progress %.',
            'cases.*.task_components.*.sub_components.*.percent.integer'  => 'Progress % must be a whole number.',
            'cases.*.task_components.required' => 'Each case needs at least one task component.',
            'cases.*.task_components.min'      => 'Each case needs at least one task component.',
            'cases.*.task_components.*.name.required' => 'Each task component needs a name.',
            'cases.*.task_components.*.sub_components.required' => 'Each task component needs at least one sub task component.',
            'cases.*.task_components.*.sub_components.min'      => 'Each task component needs at least one sub task component.',
            'cases.*.task_components.*.sub_components.*.name.required' => 'Each sub task component needs a name.',
            'tomorrow_plans.required' => 'Please add at least one plan for tomorrow.',
            'tomorrow_plans.min'      => 'Please add at least one plan for tomorrow.',
        ]);

        // cases / tomorrow_plans were already normalised above.
        $validated['total_hours'] = Report::calculateHours($validated['work_in'], $validated['work_out']);

        return $validated;
    }

    /**
     * Normalise the nested Case -> Task Component -> Sub Task Component tree,
     * dropping empty sub-components, empty task components and empty cases.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function cleanCases(array $cases): array
    {
        return collect($cases)->map(function ($case) {
            $case = (array) $case;

            $tasks = collect($case['task_components'] ?? [])->map(function ($task) {
                $task = (array) $task;

                $subs = collect($task['sub_components'] ?? [])
                    ->filter(fn ($s) => trim((string) (is_array($s) ? ($s['name'] ?? '') : '')) !== '')
                    ->map(fn ($s) => [
                        'name'    => trim((string) ($s['name'] ?? '')),
                        'percent' => ($s['percent'] ?? '') === '' || $s['percent'] === null ? null : (int) $s['percent'],
                    ])
                    ->values()
                    ->all();

                return [
                    'name'           => trim((string) ($task['name'] ?? '')),
                    'sub_components' => $subs,
                ];
            })
            ->filter(fn ($t) => $t['name'] !== '' || ! empty($t['sub_components']))
            ->values()
            ->all();

            return [
                'system_id'       => trim((string) ($case['system_id'] ?? '')),
                'case_no'         => trim((string) ($case['case_no'] ?? '')),
                'time_h'          => ($case['time_h'] ?? '') === '' || $case['time_h'] === null ? null : (float) $case['time_h'],
                'task_components' => $tasks,
            ];
        })
        ->filter(fn ($c) => $c['system_id'] !== '' || $c['case_no'] !== '' || $c['time_h'] !== null || ! empty($c['task_components']))
        ->values()
        ->all();
    }

    /** Managers available as report recipients. */
    protected function managers()
    {
        return User::where('role', 'manager')->orderBy('name')->get();
    }

    /**
     * Managed System IDs with their case numbers, shaped for the report form's
     * dependent dropdowns: [{code, cases: [caseNo, ...]}, ...].
     *
     * @return array<int, array<string, mixed>>
     */
    protected function systemIdOptions(): array
    {
        return SystemId::with('caseNumbers')->orderBy('code')->get()
            ->map(fn (SystemId $s) => [
                'code'  => $s->code,
                'cases' => $s->caseNumbers->pluck('code')->values()->all(),
            ])
            ->all();
    }

    protected function authorizeOwner(Request $request, Report $report): void
    {
        abort_unless($report->user_id === $request->user()->id, 403);
    }

    protected function authorizeView(Request $request, Report $report): void
    {
        $user = $request->user();
        abort_unless(
            $report->user_id === $user->id || $report->manager_id === $user->id || $user->isAdmin(),
            403
        );
    }
}
