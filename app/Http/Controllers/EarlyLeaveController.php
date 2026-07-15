<?php

namespace App\Http\Controllers;

use App\Mail\EarlyLeaveMail;
use App\Models\EarlyLeave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EarlyLeaveController extends Controller
{
    public function index(Request $request)
    {
        $applications = $request->user()->earlyLeaves()->latest('notice_date')->paginate(15);

        return view('early.index', compact('applications'));
    }

    public function create(Request $request)
    {
        $user = $request->user();

        return view('early.create', [
            'early' => new EarlyLeave([
                'notice_date'   => now()->toDateString(),
                'english_name'   => $user->name,
                'japanese_name'  => $user->japanese_name ?: $user->name,
                'department_name' => $user->department_name,
                'leave_time'     => '17:00',
                'to_emails'     => optional($user->manager)->email ? [$user->manager->email] : [],
                'cc_emails'     => [],
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $early = $request->user()->earlyLeaves()->create($data);

        return redirect()->route('early.show', $early)
            ->with('status', 'Early-leave application saved. Preview it below, then send or copy it.');
    }

    public function show(Request $request, EarlyLeave $early)
    {
        $this->authorizeOwner($request, $early);

        return view('early.show', [
            'early'   => $early,
            'preview' => $early->renderTemplate(),
        ]);
    }

    public function destroy(Request $request, EarlyLeave $early)
    {
        $this->authorizeOwner($request, $early);

        $early->delete();

        return redirect()->route('early.index')->with('status', 'Early-leave application deleted.');
    }

    /**
     * E-mail the application to the saved recipients (falling back to the manager).
     */
    public function send(Request $request, EarlyLeave $early)
    {
        $this->authorizeOwner($request, $early);

        $to = $early->to_emails ?: [];
        if (empty($to) && ($manager = $request->user()->manager) && $manager->email) {
            $to = [$manager->email];
        }

        if (empty($to)) {
            return back()->with('error', 'No recipient set. Add a "To" address (or ask an admin to assign your manager).');
        }

        $cc = $early->cc_emails ?: [];

        $mail = Mail::to($to);
        if (! empty($cc)) {
            $mail->cc($cc);
        }
        $mail->send(new EarlyLeaveMail($early));

        $early->update(['sent_at' => now()]);

        $ccNote = ! empty($cc) ? ' (cc: ' . implode(', ', $cc) . ')' : '';

        return redirect()->route('early.show', $early)
            ->with('status', 'Early-leave application e-mailed to ' . implode(', ', $to) . $ccNote . '.');
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
            'leave_time'      => ['required', 'date_format:H:i'],
            'to_emails'       => ['required', 'array', 'min:1'],
            'to_emails.*'     => ['email'],
            'cc_emails'       => ['nullable', 'array'],
            'cc_emails.*'     => ['email'],
            'subject'         => ['nullable', 'string', 'max:255'],
        ], [
            'to_emails.required' => 'Please enter at least one To (recipient) e-mail address.',
            'to_emails.*.email'       => 'Each To entry must be a valid e-mail address.',
            'cc_emails.*.email'       => 'Each CC entry must be a valid e-mail address.',
            'reason.required'         => 'Please enter a reason.',
            'department_name.required' => 'Please enter the department.',
            'leave_time.required'     => 'Please enter the leave time.',
            'leave_time.date_format'  => 'Leave time must be in HH:MM format.',
            'english_name.required'   => 'Please enter the English name.',
            'japanese_name.required'  => 'Please enter the Japanese name.',
        ]);
    }

    protected function authorizeOwner(Request $request, EarlyLeave $early): void
    {
        abort_unless($early->user_id === $request->user()->id, 403);
    }
}
