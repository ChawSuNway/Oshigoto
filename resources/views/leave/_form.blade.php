@php
    $user = auth()->user();
    // Existing record whose English name isn't the current user's -> it was filed on behalf.
    $defaultMode = ($leave->exists && $leave->english_name && $leave->english_name !== $user->name) ? 'proxy' : 'self';
    $method = $method ?? 'POST';
    $submitLabel = $submitLabel ?? 'Save Application';
    $cancelUrl = $cancelUrl ?? route('leave.index');
    $departmentOptions = \App\Models\Department::orderBy('name')->pluck('name');
    // Lock the Japanese name only when the profile already has one.
    $jpLocked = filled($user->japanese_name);
@endphp

{{--
    NOTE: Japanese text in this file is written as ASCII-safe escapes on purpose
    (\uXXXX in the JS getters, &#NNNNN; HTML entities in the markup) so the file
    stays pure ASCII and cannot be mangled by a non-UTF-8 editor save.
    The browser / Alpine decode them back to Japanese at runtime.
--}}

<div class="bg-white shadow-md-1 sm:rounded-lg p-6"
     x-data="{
        mode: @js(old('mode', $defaultMode)),
        fromDate: @js(old('from_date', optional($leave->from_date)->format('Y-m-d') ?: now()->toDateString())),
        toDate: @js(old('to_date', optional($leave->to_date)->format('Y-m-d') ?: now()->toDateString())),
        selfEnglish: @js($user->name),
        selfJapanese: @js($user->japanese_name ?: $user->name),
        selfDept: @js($user->department_name),
        englishName: @js(old('english_name', $leave->english_name)),
        japaneseName: @js(old('japanese_name', $leave->japanese_name)),
        department: @js(old('department_name', $departmentOptions->contains($leave->department_name) ? $leave->department_name : '')),
        reason: @js(old('reason', $leave->reason)),
        setMode(m) {
            this.mode = m;
            // Only the English name differs - it names the person the leave is for.
            // The Japanese greeting (name@department) is always the current user (the sender).
            this.englishName = (m === 'self') ? this.selfEnglish : '';
        },
        fmt(d) { if (! d) return ''; const [y, m, dd] = d.split('-'); return dd + '-' + m + '-' + y; },
        get dateBlock() {
            if (! this.fromDate) return '\u3010DD-MM-YYYY\u3011';
            if (! this.toDate || this.toDate === this.fromDate) return '\u3010' + this.fmt(this.fromDate) + '\u3011';
            return '\u3010' + this.fmt(this.fromDate) + '\u3011\u304b\u3089\u3010' + this.fmt(this.toDate) + '\u3011\u307e\u3067';
        },
        get subject() { return this.dateBlock + ' Request for Leave application of ' + (this.englishName || '[English name]'); },
        get body() {
            return '\u95a2\u4fc2\u8005\u5404\u4f4d\n\n\u304a\u75b2\u308c\u69d8\u3067\u3059\u3002' + (this.japaneseName || '[Japanese name]') + '@' + (this.department || '[department]') + '\u3067\u3059\u3002\n\n'
                + (this.reason || '[reason]') + '\u3001' + this.dateBlock + '\u4f11\u307f\u3067\u3059\u3002\n\n'
                + '\u4ee5\u4e0a\u3001\u3088\u308d\u3057\u304f\u304a\u9858\u3044\u3044\u305f\u3057\u307e\u3059\u3002';
        },
     }">
    <form method="POST" action="{{ $action }}" class="space-y-6" data-validate>
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        {{-- Applicant mode: for myself / on behalf of someone --}}
        <div>
            <x-input-label value="Applying for" />
            <div class="mt-2 inline-flex rounded-lg border border-gray-200 p-1 bg-gray-50">
                <button type="button" @click="setMode('self')"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition"
                        :class="mode === 'self' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'">
                    Myself&#65288;&#33258;&#20998;&#65289;
                </button>
                <button type="button" @click="setMode('proxy')"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition"
                        :class="mode === 'proxy' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'">
                    On behalf&#65288;&#20195;&#29702;&#65289;
                </button>
            </div>
            <input type="hidden" name="mode" x-bind:value="mode" />
            <p class="mt-1 text-xs text-gray-500"
               x-text="mode === 'self' ? 'Your name and department are filled in automatically.' : 'Enter the English name of the person taking leave. Your Japanese name and department are used in the greeting.'"></p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <x-input-label for="from_date" value="From Date" />
                <x-text-input id="from_date" name="from_date" type="date" class="mt-1 block w-full"
                              x-model="fromDate" x-on:change="if (! toDate || toDate < fromDate) toDate = fromDate" required />
                <x-input-error :messages="$errors->get('from_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="to_date" value="To Date" />
                <x-text-input id="to_date" name="to_date" type="date" class="mt-1 block w-full"
                              x-model="toDate" x-bind:min="fromDate" required />
                <p class="mt-1 text-xs text-gray-500">Same as From Date for a single day.</p>
                <x-input-error :messages="$errors->get('to_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="english_name" value="English Name" />
                <x-text-input id="english_name" name="english_name" type="text" class="mt-1 block w-full"
                              x-model="englishName" x-bind:readonly="mode === 'self'"
                              x-bind:class="mode === 'self' ? 'bg-gray-100 text-gray-600' : ''"
                              x-bind:placeholder="mode === 'proxy' ? 'Person taking the leave' : ''" required />
                <p class="mt-1 text-xs text-gray-500" x-show="mode === 'proxy'" x-cloak>The person the leave is for (shown in the subject).</p>
                <x-input-error :messages="$errors->get('english_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="japanese_name">Japanese Name (&#27663;&#21517;)</x-input-label>
                <x-text-input id="japanese_name" name="japanese_name" type="text"
                              class="mt-1 block w-full {{ $jpLocked ? 'bg-gray-100 text-gray-600' : '' }}"
                              x-model="japaneseName" :readonly="$jpLocked" required />
                <p class="mt-1 text-xs text-gray-500">
                    {{ $jpLocked ? 'From your profile - appears in the greeting line.' : 'Enter your Japanese name - it appears in the greeting line.' }}
                </p>
                <x-input-error :messages="$errors->get('japanese_name')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="department_name">Department&#65288;&#37096;&#32626;&#21517;&#65289;</x-input-label>
                <select id="department_name" name="department_name" x-model="department" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">--- Select Department ---</option>
                    @foreach ($departmentOptions as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Appears in the greeting line.</p>
                <x-input-error :messages="$errors->get('department_name')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="reason">Reason&#65288;&#29702;&#30001;&#65289;</x-input-label>
            <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full"
                          x-model="reason"
                          x-bind:placeholder="'e.g. \u79c1\u7528\u306e\u305f\u3081 / \u5e30\u7701\u306e\u305f\u3081 / \u4f53\u8abf\u4e0d\u826f'" required />
            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
        </div>

        {{-- Mail delivery fields --}}
        <div class="rounded-lg border border-gray-200 p-4 space-y-4 bg-gray-50/60">
            <h3 class="text-sm font-semibold text-gray-700">Mail Settings</h3>

            <div>
                <x-input-label for="subject" value="Subject (auto)" />
                <input id="subject" name="subject" type="text" readonly x-bind:value="subject"
                       class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-600 shadow-sm text-sm" />
                <p class="mt-1 text-xs text-gray-500">Automatically set from the dates and English name.</p>
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="to_emails" value="To" />
                <x-text-input id="to_emails" name="to_emails" type="text" class="mt-1 block w-full"
                              :value="implode(', ', (array) old('to_emails', $leave->to_emails))"
                              placeholder="e.g. manager@example.com, teamlead@example.com" />
                <p class="mt-1 text-xs text-gray-500">Separate multiple addresses with commas. At least one address is required.</p>
                <x-input-error :messages="$errors->get('to_emails')" class="mt-2" />
                @foreach ($errors->get('to_emails.*') as $toErrors)
                    <x-input-error :messages="$toErrors" class="mt-2" />
                @endforeach
            </div>

            <div>
                <x-input-label for="cc_emails" value="CC (optional)" />
                <x-text-input id="cc_emails" name="cc_emails" type="text" class="mt-1 block w-full"
                              :value="implode(', ', (array) old('cc_emails', $leave->cc_emails))"
                              placeholder="e.g. hr@example.com" />
                <p class="mt-1 text-xs text-gray-500">Separate multiple addresses with commas.</p>
                <x-input-error :messages="$errors->get('cc_emails')" class="mt-2" />
                @foreach ($errors->get('cc_emails.*') as $ccErrors)
                    <x-input-error :messages="$ccErrors" class="mt-2" />
                @endforeach
            </div>
        </div>

        {{-- Live preview --}}
        <div>
            <span class="block text-xs font-medium text-gray-500 mb-1">Live preview</span>
            <pre class="whitespace-pre-wrap font-mono text-sm bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800 leading-relaxed"><span x-text="body"></span></pre>
        </div>

        <div class="flex items-center gap-4 pt-2 border-t">
            <x-primary-button>{{ $submitLabel }}</x-primary-button>
            <a href="{{ $cancelUrl }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
</div>
