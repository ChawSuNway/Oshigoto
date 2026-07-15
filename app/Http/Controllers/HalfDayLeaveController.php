<?php

namespace App\Http\Controllers;

use App\Mail\HalfDayLeaveMail;
use App\Models\HalfDayLeave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class HalfDayLeaveController extends Controller
{
    public function index(Request $request)
    {
        $applications = $request->user()->halfDayLeaves()->latest('notice_date')->paginate(15);

        return view('half.index', compact('applications'));
    }

    public function create(Request $request)
    {
        $user = $request->user();

        return view('half.create', [
            'half' => new HalfDayLeave([
                'notice_date'    => now()->toDateString(),
                'english_name'    => $user->name,
                'japanese_name'   => $user->japanese_name ?: $user->name,
                'department_name' => $user->department_name,
                'leave_type'     => '午前',
                'to_emails'      => optional($user->manager)->email ? [$user->manager->email] : [],
                'cc_emails'      => [],
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $half = $request->user()->halfDayLeaves()->create($data);

        return redirect()->route('half.show', $half)
            ->with('status', 'Half-day-leave application saved. Preview it below, then send or copy it.');
    }

    public function show(Request $request, HalfDayLeave $half)
    {
        $this->authorizeOwner($request, $half);

        return view('half.show', [
            'half'    => $half,
            'preview' => $half->renderTemplate(),
        ]);
    }

    public function edit(Request $request, HalfDayLeave $half)
    {
        $this->authorizeOwner($request, $half);

        return view('half.edit', compact('half'));
    }

    public function update(Request $request, HalfDayLeave $half)
    {
        $this->authorizeOwner($request, $half);

        $half->update($this->validated($request));

        return redirect()->route('half.show', $half)
            ->with('status', 'Half-day-leave application updated. Preview it below, then send or copy it.');
    }

    public function destroy(Request $request, HalfDayLeave $half)
    {
        $this->authorizeOwner($request, $half);

        $half->delete();

        return redirect()->route('half.index')->with('status', 'Half-day-leave application deleted.');
    }

    /**
     * E-mail the application to the saved recipients (falling back to the manager).
     */
    public function send(Request $request, HalfDayLeave $half)
    {
        $this->authorizeOwner($request, $half);

        $to = $half->to_emails ?: [];
        if (empty($to) && ($manager = $request->user()->manager) && $manager->email) {
            $to = [$manager->email];
        }

        if (empty($to)) {
            return back()->with('error', 'No recipient set. Add a "To" address (or ask an admin to assign your manager).');
        }

        $cc = $half->cc_emails ?: [];

        $mail = Mail::to($to);
        if (! empty($cc)) {
            $mail->cc($cc);
        }
        $mail->send(new HalfDayLeaveMail($half));

        $half->update(['sent_at' => now()]);

        $ccNote = ! empty($cc) ? ' (cc: ' . implode(', ', $cc) . ')' : '';

        return redirect()->route('half.show', $half)
            ->with('status', 'Half-day-leave application e-mailed to ' . implode(', ', $to) . $ccNote . '.');
    }

    // ---------------------------------------------------------------------

    protected function validated(Request $request): array
    {
        foreach (['to_emails', 'cc_emails'] as $field) {
            $list = collect(preg_split('/[,;\s]+/', (string) $request->input($field, ''), -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($e) => trim($e))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $request->merge([$field => $list]);
        }

        return $request->validate([
            'notice_date'     => ['required', 'date'],
            'english_name'    => ['required', 'string', 'max:255'],
            'japanese_name'   => ['required', 'string', 'max:255'],
            'department_name' => ['required', 'string', 'max:255'],
            'reason'          => ['required', 'string', 'max:255'],
            'leave_type'      => ['required', 'in:午前,午後'],
            'to_emails'       => ['required', 'array', 'min:1'],
            'to_emails.*'     => ['email'],
            'cc_emails'       => ['nullable', 'array'],
            'cc_emails.*'     => ['email'],
            'subject'         => ['nullable', 'string', 'max:255'],
        ], [
            'to_emails.required' => 'Please enter at least one To (recipient) e-mail address.',
            'to_emails.*.email'        => 'Each To entry must be a valid e-mail address.',
            'cc_emails.*.email'        => 'Each CC entry must be a valid e-mail address.',
            'reason.required'          => 'Please enter a reason.',
            'department_name.required' => 'Please enter the department.',
            'leave_type.required'      => 'Please choose 午前 or 午後.',
            'leave_type.in'            => 'Leave type must be either 午前 or 午後.',
            'english_name.required'    => 'Please enter the English name.',
            'japanese_name.required'   => 'Please enter the Japanese name.',
        ]);
    }

    protected function authorizeOwner(Request $request, HalfDayLeave $half): void
    {
        abort_unless($half->user_id === $request->user()->id, 403);
    }
}
