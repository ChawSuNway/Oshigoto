<?php

namespace App\Http\Controllers;

use App\Mail\LeaveApplicationMail;
use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LeaveApplicationController extends Controller
{
    use \App\Http\Controllers\Concerns\ListFilters;

    /**
     * Admins list every user's records; everyone else only their own.
     */
    public function index(Request $request)
    {
        $query = $this->baseListQuery($request, LeaveApplication::class);

        $this->applyUserFilter($query, $request);
        $this->applyDepartmentFilter($query, $request);
        $this->applyDateOverlap($query, $request, 'from_date', 'to_date');
        $this->applyStatusFilter($query, $request);

        $applications = $query->latest('from_date')->paginate(15)->withQueryString();

        return view('leave.index', array_merge(
            ['applications' => $applications],
            $this->filterOptions($request),
        ));
    }

    public function create(Request $request)
    {
        $this->denyAdminEntry($request);

        $user = $request->user();

        return view('leave.create', [
            'leave' => new LeaveApplication([
                'from_date'       => now()->toDateString(),
                'to_date'         => now()->toDateString(),
                'english_name'    => $user->name,
                'japanese_name'   => $user->japanese_name ?: $user->name,
                'department_name' => $user->department_name,
                'to_emails'       => optional($user->manager)->email ? [$user->manager->email] : [],
                'cc_emails'       => [],
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->denyAdminEntry($request);

        $leave = $request->user()->leaveApplications()->create($this->validated($request));

        return redirect()->route('leave.show', $leave)
            ->with('status', 'Leave application saved. Preview it below, then send or copy it.');
    }

    public function show(Request $request, LeaveApplication $leave)
    {
        $this->authorizeView($request, $leave);

        return view('leave.show', [
            'leave'   => $leave,
            'preview' => $leave->renderTemplate(),
        ]);
    }

    public function edit(Request $request, LeaveApplication $leave)
    {
        $this->authorizeOwner($request, $leave);

        return view('leave.edit', compact('leave'));
    }

    public function update(Request $request, LeaveApplication $leave)
    {
        $this->authorizeOwner($request, $leave);

        $leave->update($this->validated($request));

        return redirect()->route('leave.show', $leave)
            ->with('status', 'Leave application updated. Preview it below, then send or copy it.');
    }

    public function destroy(Request $request, LeaveApplication $leave)
    {
        $this->authorizeOwner($request, $leave);

        $leave->delete();

        return redirect()->route('leave.index')->with('status', 'Leave application deleted.');
    }

    /**
     * E-mail the application to the saved recipients (falling back to the manager).
     */
    public function send(Request $request, LeaveApplication $leave)
    {
        $this->authorizeOwner($request, $leave);

        $to = $leave->to_emails ?: [];
        if (empty($to) && ($manager = $request->user()->manager) && $manager->email) {
            $to = [$manager->email];
        }

        if (empty($to)) {
            return back()->with('error', 'No recipient set. Add a "To" address (or ask an admin to assign your manager).');
        }

        $cc = $leave->cc_emails ?: [];

        $mail = Mail::to($to);
        if (! empty($cc)) {
            $mail->cc($cc);
        }
        $mail->send(new LeaveApplicationMail($leave));

        $leave->update(['sent_at' => now()]);

        $ccNote = ! empty($cc) ? ' (cc: ' . implode(', ', $cc) . ')' : '';

        return redirect()->route('leave.show', $leave)
            ->with('status', 'Leave application e-mailed to ' . implode(', ', $to) . $ccNote . '.');
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
            'from_date'       => ['required', 'date'],
            'to_date'         => ['required', 'date', 'after_or_equal:from_date'],
            'english_name'    => ['required', 'string', 'max:255'],
            'japanese_name'   => ['required', 'string', 'max:255'],
            'department_name' => ['required', 'string', 'max:255'],
            'reason'          => ['required', 'string', 'max:255'],
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
            'from_date.required'       => 'Please choose the start date.',
            'to_date.required'         => 'Please choose the end date.',
            'to_date.after_or_equal'   => 'The end date must be the same as or after the start date.',
            'english_name.required'    => 'Please enter the English name.',
            'japanese_name.required'   => 'Please enter the Japanese name.',
        ]);
    }

    protected function authorizeOwner(Request $request, LeaveApplication $leave): void
    {
        abort_unless($leave->user_id === $request->user()->id, 403);
    }

    /** Owners see their own record; admins may review anyone's. */
    protected function authorizeView(Request $request, LeaveApplication $leave): void
    {
        $user = $request->user();

        abort_unless($leave->user_id === $user->id || $user->isAdmin(), 403);
    }
}
