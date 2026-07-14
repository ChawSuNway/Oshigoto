@php
    $initCases = old('case_numbers', $systemId->exists
        ? $systemId->caseNumbers->map(fn ($c) => ['code' => $c->code, 'description' => $c->description])->all()
        : []);
    if (empty($initCases)) {
        $initCases = [['code' => '', 'description' => '']];
    }
@endphp

<div class="space-y-6"
     x-data="{
        cases: {{ Illuminate\Support\Js::from($initCases) }},
        addCase() { this.cases.push({ code: '', description: '' }); },
        removeCase(i) { this.cases.splice(i, 1); if (! this.cases.length) this.cases.push({ code: '', description: '' }); },
     }">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <x-input-label for="code" value="System ID" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full"
                          :value="old('code', $systemId->code)" placeholder="e.g. BTS" required />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="description" value="Description (optional)" />
            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full"
                          :value="old('description', $systemId->description)" placeholder="e.g. Blood Transfusion System" />
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>
    </div>

    {{-- Case numbers (one system → many case numbers) --}}
    <div>
        <div class="flex items-center justify-between mb-2">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Case Numbers</h3>
                <p class="text-xs text-gray-500 mt-0.5">These appear in the report form once a matching System ID is chosen.</p>
            </div>
            <button type="button" @click="addCase()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add case no.
            </button>
        </div>

        <div class="space-y-2">
            <template x-for="(c, i) in cases" :key="i">
                <div class="grid grid-cols-12 gap-2 items-center">
                    <input type="text" placeholder="Case No. — e.g. #1234" class="col-span-5 border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                           :name="`case_numbers[${i}][code]`" x-model="c.code">
                    <input type="text" placeholder="Description (optional)" class="col-span-6 border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                           :name="`case_numbers[${i}][description]`" x-model="c.description">
                    <button type="button" @click="removeCase(i)" title="Remove"
                            class="col-span-1 justify-self-center rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <div class="flex items-center gap-4 pt-4 border-t">
        <x-primary-button>{{ $submitLabel ?? 'Save' }}</x-primary-button>
        <a href="{{ route('system-ids.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
    </div>
</div>
