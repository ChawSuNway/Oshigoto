<?php

namespace App\Http\Controllers;

use App\Mail\LateComingMail;
use App\Models\LateComing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LateComingController extends Controller
{
    public function index(Request $request)
    {
        $notices = $request->user()->lateComings()->latest('notice_date')->paginate(15);

        return view('late.index', compact('notices'));
    }

    public function create(Request $request)
    {
        $user = $request->user();

        return view('late.create', [
            'late' => new LateComing([
                'notice_date'   => now()->toDateString(),
                'english_name'  => $user->name,
                'japanese_name' => $user->japanese_name ?: $user->name,
                'minutes'       => 30,
                'to_emails'     => optional($user->manager)->email ? [$user->manager->email] : [],
                'cc_emails'     => [],
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $late = $request->user()->lateComings()->create($data);

        return redirect()->route('late.show', $late)
            ->with('status', 'Late-coming notice saved. Preview it below, then send or copy it.');
    }

    public function show(Request $request, LateComing $late)
    {
        $this->authorizeOwner($request, $late);

        return view('late.show', [
            'late'    => $late,
            'preview' => $late->renderTemplate(),
        ]);
    }

    public function destroy(Request $request, LateComing $late)
    {
        $this->authorizeOwner($request, $late);

        $late->delete();

        return redirect()->route('late.index')->with('status', 'Late-coming notice deleted.');
    }

    /**
     * E-mail the notice to the user's manager.
     */
    public function send(Request $request, LateComing $late)
    {
        $this->authorizeOwner($request, $late);

        // Recipients: the saved To list, falling back to the user's manager.
        $to = $late->to_emails ?: [];
        if (empty($to) && ($manager = $request->user()->manager) && $manager->email) {
            $to = [$manager->email];
        }

        if (empty($to)) {
            return back()->with('error', 'No recipient set. Add a "To" address (or ask an admin to assign your manager).');
        }

        $cc = $late->cc_emails ?: [];

        $mail = Mail::to($to);
        if (! empty($cc)) {
            $mail->cc($cc);
        }
        $mail->send(new LateComingMail($late));

        $late->update(['sent_at' => now()]);

        $ccNote = ! empty($cc) ? ' (cc: ' . implode(', ', $cc) . ')' : '';

        return redirect()->route('late.show', $late)
            ->with('status', 'Late-coming notice e-mailed to ' . implode(', ', $to) . $ccNote . '.');
    }

    // ---------------------------------------------------------------------

    protected function validated(Request $request): array
    {
        // Accept To / CC as a comma / semicolon / whitespace separated list; normalise to arrays.
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
            'notice_date'   => ['required', 'date'],
            'english_name'  => ['required', 'string', 'max:255'],
            'japanese_name' => ['required', 'string', 'max:255'],
            'reason'        => ['required', 'string', 'max:255'],
            'minutes'       => ['required', 'integer', 'min:1', 'max:1440'],
            'to_emails'     => ['nullable', 'array'],
            'to_emails.*'   => ['email'],
            'cc_emails'     => ['nullable', 'array'],
            'cc_emails.*'   => ['email'],
            'subject'       => ['nullable', 'string', 'max:255'],
        ], [
            'to_emails.*.email'    => 'Each To entry must be a valid e-mail address.',
            'cc_emails.*.email'    => 'Each CC entry must be a valid e-mail address.',
            'reason.required'      => 'Please enter a reason.',
            'minutes.required'     => 'Please enter how many minutes late.',
            'minutes.integer'      => 'Minutes must be a whole number.',
            'minutes.min'          => 'Minutes must be at least 1.',
            'minutes.max'          => 'Minutes must be 1440 (24h) or less.',
            'english_name.required'  => 'Please enter the English name.',
            'japanese_name.required' => 'Please enter the Japanese name.',
        ]);
    }

    protected function authorizeOwner(Request $request, LateComing $late): void
    {
        abort_unless($late->user_id === $request->user()->id, 403);
    }
}
