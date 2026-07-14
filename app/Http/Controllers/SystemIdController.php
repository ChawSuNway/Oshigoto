<?php

namespace App\Http\Controllers;

use App\Models\SystemId;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SystemIdController extends Controller
{
    public function index()
    {
        $systemIds = SystemId::withCount('caseNumbers')->orderBy('code')->paginate(20);

        return view('system_ids.index', compact('systemIds'));
    }

    public function create()
    {
        return view('system_ids.create', ['systemId' => new SystemId()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $systemId = SystemId::create([
            'code'        => $data['code'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncCaseNumbers($systemId, $data['case_numbers'] ?? []);

        return redirect()->route('system-ids.index')->with('status', "System ID \"{$systemId->code}\" saved.");
    }

    public function edit(SystemId $systemId)
    {
        $systemId->load('caseNumbers');

        return view('system_ids.edit', compact('systemId'));
    }

    public function update(Request $request, SystemId $systemId)
    {
        $data = $this->validated($request, $systemId);

        $systemId->update([
            'code'        => $data['code'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncCaseNumbers($systemId, $data['case_numbers'] ?? []);

        return redirect()->route('system-ids.index')->with('status', "System ID \"{$systemId->code}\" updated.");
    }

    public function destroy(SystemId $systemId)
    {
        $systemId->delete();

        return redirect()->route('system-ids.index')->with('status', 'System ID deleted.');
    }

    // ---------------------------------------------------------------------

    protected function validated(Request $request, ?SystemId $systemId = null): array
    {
        return $request->validate([
            'code'                     => ['required', 'string', 'max:255', Rule::unique('system_ids', 'code')->ignore($systemId?->id)],
            'description'              => ['nullable', 'string', 'max:255'],
            'case_numbers'             => ['nullable', 'array'],
            'case_numbers.*.code'      => ['nullable', 'string', 'max:255'],
            'case_numbers.*.description' => ['nullable', 'string', 'max:255'],
        ], [
            'code.required' => 'Please enter a System ID.',
            'code.unique'   => 'This System ID already exists.',
        ]);
    }

    /**
     * Replace the system's case numbers with the submitted set (deduped, non-empty).
     */
    protected function syncCaseNumbers(SystemId $systemId, array $rows): void
    {
        $seen = [];
        $clean = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            if ($code === '' || isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;
            $clean[] = [
                'code'        => $code,
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
            ];
        }

        $systemId->caseNumbers()->delete();
        if (! empty($clean)) {
            $systemId->caseNumbers()->createMany($clean);
        }
    }
}
