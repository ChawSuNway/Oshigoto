@php
    // Normalise cases into the nested Case -> Task Component -> Sub Task Component shape,
    // seeding one empty row at each level and tolerating older flat records.
    $rawCases = old('cases', $report->cases ?? []);
    $emptySub = ['name' => '', 'percent' => ''];
    $emptyTask = ['name' => '', 'sub_components' => [$emptySub]];
    $initCases = collect($rawCases)->map(function ($c) use ($emptySub, $emptyTask) {
        $c = (array) $c;
        $tasks = collect($c['task_components'] ?? [])->map(function ($t) use ($emptySub) {
            $t = (array) $t;
            $subs = collect($t['sub_components'] ?? [])
                ->map(fn ($s) => ['name' => $s['name'] ?? '', 'percent' => $s['percent'] ?? ''])
                ->all();
            return ['name' => $t['name'] ?? '', 'sub_components' => $subs ?: [$emptySub]];
        })->all();
        return [
            'system_id'       => $c['system_id'] ?? '',
            'case_no'         => $c['case_no'] ?? '',
            'time_h'          => $c['time_h'] ?? '',
            'task_components' => $tasks ?: [$emptyTask],
        ];
    })->all();
    if (empty($initCases)) {
        $initCases = [['system_id' => '', 'case_no' => '', 'time_h' => '', 'task_components' => [$emptyTask]]];
    }

    $initPlans = old('tomorrow_plans', ! empty($report->tomorrow_plans) ? $report->tomorrow_plans : ['']);
@endphp

<div class="space-y-8"
     x-data="{
        managers: {{ Illuminate\Support\Js::from($managers->mapWithKeys(fn ($m) => [$m->id => $m->name])) }},
        managerId: '{{ old('manager_id', $report->manager_id) }}',
        managerName: @js(old('manager_name', $report->manager_name)),
        workIn: '{{ old('work_in', $report->timeHm($report->work_in) ?: '08:00') }}',
        workOut: '{{ old('work_out', $report->timeHm($report->work_out) ?: '17:00') }}',
        cases: {{ Illuminate\Support\Js::from($initCases) }},
        plans: {{ Illuminate\Support\Js::from($initPlans) }},
        systemIds: {{ Illuminate\Support\Js::from($systemIds ?? []) }},
        casesFor(code) { const s = this.systemIds.find(s => s.code === code); return s ? s.cases : []; },
        get totalHours() {
            if (! this.workIn || ! this.workOut) return '0';
            const [ih, im] = this.workIn.split(':').map(Number);
            const [oh, om] = this.workOut.split(':').map(Number);
            let mins = (oh * 60 + om) - (ih * 60 + im);
            if (mins < 0) mins += 24 * 60; // overnight shift
            let hours = mins / 60;
            // Mirror Report::calculateHours(): deduct a 1-hour lunch for a full day (> 6h).
            if (hours > 6) hours -= 1;
            return (Math.round(Math.max(hours, 0) * 100) / 100).toString();
        },
        onManagerChange() {
            if (this.managerId && this.managers[this.managerId]) {
                this.managerName = this.managers[this.managerId];
            }
        },
        showPreview: true,
        blankTask() { return { name: '', sub_components: [{ name: '', percent: '' }] }; },
        blankCase() { return { system_id: '', case_no: '', time_h: '', task_components: [this.blankTask()] }; },
        addCase() { this.cases.push(this.blankCase()); },
        dupCase(ci) { this.cases.splice(ci + 1, 0, JSON.parse(JSON.stringify(this.cases[ci]))); },
        removeCase(ci) { this.cases.splice(ci, 1); if (! this.cases.length) this.cases.push(this.blankCase()); },
        addTask(c) { c.task_components.push(this.blankTask()); },
        dupTask(c, ti) { c.task_components.splice(ti + 1, 0, JSON.parse(JSON.stringify(c.task_components[ti]))); },
        removeTask(c, ti) { c.task_components.splice(ti, 1); if (! c.task_components.length) c.task_components.push(this.blankTask()); },
        addSub(t) { t.sub_components.push({ name: '', percent: '' }); },
        removeSub(t, si) { t.sub_components.splice(si, 1); if (! t.sub_components.length) t.sub_components.push({ name: '', percent: '' }); },
        pct(v) { const n = parseInt(v); return isNaN(n) ? 0 : Math.min(Math.max(n, 0), 100); },
        get workPreview() {
            const out = [];
            this.cases.forEach(c => (c.task_components || []).forEach(t => {
                const name = (t.name || '').trim();
                if (! name) return;
                const subs = (t.sub_components || []).filter(s => (s.name || '').trim());
                out.push({ header: name, subs: subs.map(s => (s.name || '').trim()) });
            }));
            return out;
        },
        get progressPreview() {
            const out = [];
            this.cases.forEach(c => (c.task_components || []).forEach(t => {
                const name = (t.name || '').trim();
                if (! name) return;
                const subs = (t.sub_components || []).filter(s => (s.name || '').trim() && String(s.percent ?? '').trim() !== '');
                if (! subs.length) return;
                out.push({ header: name, subs: subs.map(s => `${(s.name || '').trim()} (${s.percent}%)`) });
            }));
            return out;
        },
     }">

    {{-- Basic info --}}
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-input-label for="report_date" value="Date" />
            <x-text-input id="report_date" name="report_date" type="date" class="mt-1 block w-full"
                          :value="old('report_date', optional($report->report_date)->format('Y-m-d') ?: now()->toDateString())" required />
            <x-input-error :messages="$errors->get('report_date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="user_name" value="Your Name" />
            <x-text-input id="user_name" name="user_name" type="text" class="mt-1 block w-full"
                          :value="old('user_name', $report->user_name)" required />
            <x-input-error :messages="$errors->get('user_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="manager_id" value="Manager (mail recipient)" />
            <select id="manager_id" name="manager_id" x-model="managerId" @change="onManagerChange()" required
                    @class([
                        'mt-1 block w-full rounded-md shadow-sm',
                        'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('manager_id'),
                        'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' => ! $errors->has('manager_id'),
                    ])>
                <option value="">— Select manager —</option>
                @foreach ($managers as $m)
                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->email }})</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('manager_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="manager_name" value="Manager Name (as shown in template)" />
            <x-text-input id="manager_name" name="manager_name" type="text" class="mt-1 block w-full"
                          x-model="managerName" required />
            <x-input-error :messages="$errors->get('manager_name')" class="mt-2" />
        </div>

        <div class="md:col-span-2">
            @php $ccOld = old('cc', $report->cc); $ccValue = is_array($ccOld) ? implode(', ', $ccOld) : $ccOld; @endphp
            <x-input-label for="cc" value="CC" />
            <x-text-input id="cc" name="cc" type="text" class="mt-1 block w-full" required
                          :value="$ccValue"
                          placeholder="e.g. teamlead@example.com, hr@example.com" />
            <p class="mt-1 text-xs text-gray-500">Separate multiple e-mail addresses with commas. At least one required.</p>
            <x-input-error :messages="$errors->get('cc')" class="mt-2" />
            @foreach ($errors->get('cc.*') as $ccErrors)
                <x-input-error :messages="$ccErrors" class="mt-2" />
            @endforeach
        </div>
    </section>

    {{-- Working time --}}
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
        <div>
            <x-input-label for="work_in" value="Work In" />
            <x-text-input id="work_in" name="work_in" type="time" class="mt-1 block w-full" x-model="workIn" required />
            <x-input-error :messages="$errors->get('work_in')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="work_out" value="Work Out" />
            <x-text-input id="work_out" name="work_out" type="time" class="mt-1 block w-full" x-model="workOut" required />
            <x-input-error :messages="$errors->get('work_out')" class="mt-2" />
        </div>
        <div>
            <x-input-label value="Total Net Hours" />
            <div class="mt-1 block w-full rounded-md bg-gray-100 border border-gray-300 px-3 py-2 text-gray-700">
                <span x-text="totalHours"></span> hrs
            </div>
        </div>
    </section>

    {{-- Cases tree: System ID / Case No. / Time -> Task Components -> Sub Task Components --}}
    <section>
        <div class="flex items-start justify-between mb-3 gap-4">
            <div>
                <h3 class="text-base font-semibold text-gray-800">Cases &amp; Task Breakdown</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Generates the <span class="font-medium text-gray-600">本日の作業内容</span> and
                    <span class="font-medium text-gray-600">作業進捗％</span> sections of the report.
                </p>
            </div>
            <button type="button" @click="addCase()"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add case
            </button>
        </div>

        @php
            // Gather every case-related error (top-level "cases" plus nested task/sub keys).
            $caseErrorMsgs = collect($errors->getMessages())
                ->filter(fn ($m, $k) => $k === 'cases' || \Illuminate\Support\Str::startsWith($k, 'cases.'))
                ->flatten()->unique()->values()->all();
        @endphp
        @if (! empty($caseErrorMsgs))
            <div class="mb-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 space-y-1">
                @foreach ($caseErrorMsgs as $ce)
                    <p>• {{ $ce }}</p>
                @endforeach
            </div>
        @endif

        <div @class([
                'space-y-4',
                'rounded-lg border border-red-400 p-3' => ! empty($caseErrorMsgs),
            ])>
            <template x-for="(c, ci) in cases" :key="ci">
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    {{-- Case header bar --}}
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-gray-50 px-4 py-2.5">
                        <span class="inline-flex h-6 items-center justify-center rounded-full bg-indigo-100 px-2.5 text-xs font-semibold text-indigo-700"
                              x-text="'Case ' + (ci + 1)"></span>
                        <span class="truncate text-xs text-gray-400" x-show="c.system_id || c.case_no"
                              x-text="[c.system_id, c.case_no].filter(Boolean).join(' · ')"></span>
                        <div class="ml-auto flex items-center gap-0.5">
                            <button type="button" @click="dupCase(ci)" title="Duplicate case"
                                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-200/70 hover:text-gray-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 8V5.25A2.25 2.25 0 0 1 10.25 3h8.5A2.25 2.25 0 0 1 21 5.25v8.5A2.25 2.25 0 0 1 18.75 16H16M3 10.25A2.25 2.25 0 0 1 5.25 8h8.5A2.25 2.25 0 0 1 16 10.25v8.5A2.25 2.25 0 0 1 13.75 21h-8.5A2.25 2.25 0 0 1 3 18.75v-8.5Z" /></svg>
                            </button>
                            <button type="button" @click="removeCase(ci)" title="Remove case"
                                    class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-4 space-y-4">
                        {{-- Case fields --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-gray-500">System ID</span>
                                <select class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :name="`cases[${ci}][system_id]`" x-model="c.system_id" @change="c.case_no = ''"
                                        x-init="$nextTick(() => { $el.value = c.system_id })" required>
                                    <option value="">— Select System ID —</option>
                                    <template x-for="s in systemIds" :key="s.code">
                                        <option :value="s.code" x-text="s.code"></option>
                                    </template>
                                    {{-- Preserve a legacy value not in the managed list --}}
                                    <template x-if="c.system_id && ! systemIds.some(s => s.code === c.system_id)">
                                        <option :value="c.system_id" x-text="c.system_id + ' (custom)'"></option>
                                    </template>
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-gray-500">Case No.</span>
                                <select class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :name="`cases[${ci}][case_no]`" x-model="c.case_no"
                                        x-init="$nextTick(() => { $el.value = c.case_no })" required>
                                    <option value="">— Select Case No. —</option>
                                    <template x-for="cn in casesFor(c.system_id)" :key="cn">
                                        <option :value="cn" x-text="cn"></option>
                                    </template>
                                    {{-- Preserve a legacy value not in the managed list --}}
                                    <template x-if="c.case_no && ! casesFor(c.system_id).includes(c.case_no)">
                                        <option :value="c.case_no" x-text="c.case_no + ' (custom)'"></option>
                                    </template>
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-gray-500">Time (h)</span>
                                <input type="number" step="0.25" min="0" max="24" placeholder="0.0" class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       :name="`cases[${ci}][time_h]`" x-model="c.time_h" required>
                            </label>
                        </div>

                        {{-- Task components under this case --}}
                        <div class="space-y-3 border-s-2 border-indigo-100 ps-3">
                            <template x-for="(t, ti) in c.task_components" :key="ti">
                                <div class="rounded-lg border border-gray-200 bg-gray-50/60">
                                    <div class="flex items-center gap-2 px-3 py-2">
                                        <span class="inline-flex h-5 items-center rounded bg-indigo-50 px-1.5 text-[11px] font-semibold text-indigo-600 whitespace-nowrap"
                                              x-text="'Task ' + (ti + 1)"></span>
                                        <input type="text" placeholder="Task component name — e.g. 1)BTSのアレルギー項目追加対応"
                                               class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                               :name="`cases[${ci}][task_components][${ti}][name]`" x-model="t.name">
                                        <button type="button" @click="dupTask(c, ti)" title="Duplicate task"
                                                class="rounded-md p-1.5 text-gray-400 hover:bg-gray-200/70 hover:text-gray-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 8V5.25A2.25 2.25 0 0 1 10.25 3h8.5A2.25 2.25 0 0 1 21 5.25v8.5A2.25 2.25 0 0 1 18.75 16H16M3 10.25A2.25 2.25 0 0 1 5.25 8h8.5A2.25 2.25 0 0 1 16 10.25v8.5A2.25 2.25 0 0 1 13.75 21h-8.5A2.25 2.25 0 0 1 3 18.75v-8.5Z" /></svg>
                                        </button>
                                        <button type="button" @click="removeTask(c, ti)" title="Remove task"
                                                class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </div>

                                    {{-- Sub task components under this task --}}
                                    <div class="border-t border-gray-100 bg-white px-3 py-2.5 space-y-2.5 rounded-b-lg">
                                        <template x-for="(s, si) in t.sub_components" :key="si">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-gray-300 select-none" aria-hidden="true">└</span>
                                                    <input type="text" placeholder="Sub task — e.g. XML編集"
                                                           class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                           :name="`cases[${ci}][task_components][${ti}][sub_components][${si}][name]`" x-model="s.name">
                                                    <div class="relative w-20 shrink-0">
                                                        <input type="number" min="0" max="100" placeholder="%"
                                                               class="w-full border-gray-300 rounded-md shadow-sm text-sm pe-6 focus:border-indigo-500 focus:ring-indigo-500"
                                                               :name="`cases[${ci}][task_components][${ti}][sub_components][${si}][percent]`" x-model="s.percent" required>
                                                        <span class="pointer-events-none absolute inset-y-0 end-2 flex items-center text-xs text-gray-400">%</span>
                                                    </div>
                                                    <button type="button" @click="removeSub(t, si)" title="Remove sub task"
                                                            class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                    </button>
                                                </div>
                                                {{-- progress bar mirrors the entered percent --}}
                                                <div class="ms-6 mt-1 h-1 rounded-full bg-gray-100 overflow-hidden" x-show="String(s.percent).trim() !== ''">
                                                    <div class="h-full rounded-full bg-indigo-500 transition-all" :style="`width: ${pct(s.percent)}%`"></div>
                                                </div>
                                            </div>
                                        </template>
                                        <button type="button" @click="addSub(t)"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                            Add sub task
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <button type="button" @click="addTask(c)"
                                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-dashed border-gray-300 py-2 text-sm font-medium text-gray-500 hover:border-indigo-300 hover:text-indigo-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Add task component
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Live preview of the generated report sections --}}
        <div class="mt-4" x-show="workPreview.length" x-cloak>
            <button type="button" @click="showPreview = ! showPreview"
                    class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700">
                <svg class="h-3.5 w-3.5 transition-transform" :class="showPreview && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <span x-text="showPreview ? 'Hide preview' : 'Show preview'"></span>
            </button>
            <div x-show="showPreview" x-transition class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 font-mono text-xs leading-relaxed text-gray-700">
                <div>
                    <div class="mb-1 font-sans text-[11px] font-semibold uppercase tracking-wide text-gray-400">【本日の作業内容】</div>
                    <template x-for="(g, gi) in workPreview" :key="gi">
                        <div>
                            <div x-text="'・ ' + g.header"></div>
                            <template x-for="(s, si) in g.subs" :key="si">
                                <div class="ps-6 text-gray-500" x-text="'・ ' + s"></div>
                            </template>
                        </div>
                    </template>
                </div>
                <div>
                    <div class="mb-1 font-sans text-[11px] font-semibold uppercase tracking-wide text-gray-400">【作業進捗％】</div>
                    <template x-if="! progressPreview.length">
                        <div class="text-gray-400 italic">Add a % to a sub task to include it here.</div>
                    </template>
                    <template x-for="(g, gi) in progressPreview" :key="gi">
                        <div>
                            <div x-text="'・ ' + g.header"></div>
                            <template x-for="(s, si) in g.subs" :key="si">
                                <div class="ps-6 text-gray-500" x-text="'・ ' + s"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </section>

    {{-- Problems --}}
    <section>
        <x-input-label for="problems" value="Problems（問題件） — leave blank for None" />
        <textarea id="problems" name="problems" rows="3"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                  placeholder="List blocking issues, bugs, or delays here">{{ old('problems', $report->problems) }}</textarea>
        <x-input-error :messages="$errors->get('problems')" class="mt-2" />
    </section>

    {{-- Tomorrow's plan --}}
    <section>
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-base font-semibold text-gray-800">Tomorrow Plan（明日予定）</h3>
            <button type="button" @click="plans.push('')"
                    class="text-sm text-indigo-600 hover:text-indigo-800">+ Add task</button>
        </div>
        <div @class([
                'space-y-2',
                'rounded-lg border border-red-400 p-3' => $errors->has('tomorrow_plans'),
            ])>
            <template x-for="(row, i) in plans" :key="i">
                <div class="grid grid-cols-12 gap-2 items-center">
                    <input type="text" placeholder="Task planned for the next working day" class="col-span-11 border-gray-300 rounded-md shadow-sm text-sm"
                           :name="`tomorrow_plans[${i}]`" x-model="plans[i]">
                    <button type="button" @click="plans.splice(i,1)" class="col-span-1 text-red-500 hover:text-red-700 text-lg">&times;</button>
                </div>
            </template>
        </div>
        <x-input-error :messages="$errors->get('tomorrow_plans')" class="mt-2" />
    </section>

    <div class="flex items-center gap-4 pt-4 border-t">
        <x-primary-button>{{ $submitLabel ?? 'Save Report' }}</x-primary-button>
        <a href="{{ route('reports.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
    </div>
</div>
