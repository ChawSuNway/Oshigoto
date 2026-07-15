@php
    $user = auth()->user();
    // Existing record whose English name isn't the current user's → it was filed on behalf.
    $defaultMode = ($half->exists && $half->english_name && $half->english_name !== $user->name) ? 'proxy' : 'self';
    $method = $method ?? 'POST';
    $submitLabel = $submitLabel ?? 'Save Application';
    $cancelUrl = $cancelUrl ?? route('half.index');
    $departmentOptions = \App\Models\Department::orderBy('name')->pluck('name');
    // Lock the Japanese name only when the profile already has one.
    $jpLocked = filled($user->japanese_name);
@endphp

<div class="bg-white shadow-md-1 sm:rounded-lg p-6"
     x-data="{
        mode: @js(old('mode', $defaultMode)),
        noticeDate: @js(old('notice_date', optional($half->notice_date)->format('Y-m-d') ?: now()->toDateString())),
        selfEnglish: @js($user->name),
        selfJapanese: @js($user->japanese_name ?: $user->name),
        selfDept: @js($user->department_name),
        englishName: @js(old('english_name', $half->english_name)),
        japaneseName: @js(old('japanese_name', $half->japanese_name)),
        department: @js(old('department_name', $departmentOptions->contains($half->department_name) ? $half->department_name : '')),
        reason: @js(old('reason', $half->reason)),
        leaveType: @js(old('leave_type', $half->leave_type ?: '午前')),
        setMode(m) {
            this.mode = m;
            // Only the English name differs — it names the person the leave is for.
            // The Japanese greeting (name@department) is always the current user (the sender).
            this.englishName = (m === 'self') ? this.selfEnglish : '';
        },
        get formattedDate() {
            if (! this.noticeDate) return '[DD-MM-YYYY]';
            const [y, m, d] = this.noticeDate.split('-');
            return d + '-' + m + '-' + y;
        },
        get reasonClause() {
            const r = (this.reason || '').trim();
            if (! r) return '[reason]ので';
            if (/(ため|から)$/.test(r)) return r;
            if (/(い|う|く|ぐ|す|つ|ぬ|ふ|ぶ|む|る|た|だ|ん)$/.test(r)) return r + 'ので';
            return r + 'なので';
        },
        get leaveVerb() {
            const health = ['体調','病気','発熱','風邪','通院','入院','頭痛','腹痛','不良','看病','けが','怪我','治療','診察','インフル','コロナ','休養','具合'];
            const r = this.reason || '';
            return health.some(k => r.includes(k)) ? '休ませていただきます' : '取らせていただきます';
        },
        get subject() { return '【' + this.formattedDate + '】Request for Half Day Leave application of ' + (this.englishName || '[English name]'); },
        get body() {
            return '宛先各位\n\nお疲れ様です。' + (this.japaneseName || '[Japanese name]') + '@' + (this.department || '[department]') + 'です。\n\n'
                + this.reasonClause + '、【' + this.formattedDate + '】本日「' + (this.leaveType || '[午前/午後]') + '半休」を' + this.leaveVerb + '。\n\n'
                + '以上、よろしくお願いいたします。';
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
                    Myself（自分）
                </button>
                <button type="button" @click="setMode('proxy')"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition"
                        :class="mode === 'proxy' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'">
                    On behalf（代理）
                </button>
            </div>
            <input type="hidden" name="mode" x-bind:value="mode" />
            <p class="mt-1 text-xs text-gray-500"
               x-text="mode === 'self' ? 'Your name and department are filled in automatically.' : 'Enter the English name of the person taking leave. Your Japanese name and department are used in the greeting.'"></p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <x-input-label for="notice_date" value="Date" />
                <x-text-input id="notice_date" name="notice_date" type="date" class="mt-1 block w-full"
                              x-model="noticeDate" required />
                <x-input-error :messages="$errors->get('notice_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Type（午前/午後）" />
                <div class="mt-2 flex items-center gap-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="leave_type" value="午前" x-model="leaveType"
                               class="text-indigo-600 border-gray-300 focus:ring-indigo-500" required />
                        <span class="text-sm text-gray-700">午前 (Morning)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="leave_type" value="午後" x-model="leaveType"
                               class="text-indigo-600 border-gray-300 focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700">午後 (Afternoon)</span>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('leave_type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="english_name" value="English Name" />
                <x-text-input id="english_name" name="english_name" type="text" class="mt-1 block w-full"
                              x-model="englishName" x-bind:readonly="mode === 'self'"
                              x-bind:class="mode === 'self' ? 'bg-gray-100 text-gray-600' : ''"
                              x-bind:placeholder="mode === 'proxy' ? 'Person taking the leave' : ''" required />
                <p class="mt-1 text-xs text-gray-500" x-show="mode === 'proxy'" x-cloak>The person the half-day leave is for (shown in the subject).</p>
                <x-input-error :messages="$errors->get('english_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="japanese_name" value="Japanese Name (氏名)" />
                <x-text-input id="japanese_name" name="japanese_name" type="text"
                              class="mt-1 block w-full {{ $jpLocked ? 'bg-gray-100 text-gray-600' : '' }}"
                              x-model="japaneseName" :readonly="$jpLocked" required />
                <p class="mt-1 text-xs text-gray-500">
                    {{ $jpLocked ? 'From your profile — appears in the greeting line.' : 'Enter your Japanese name (氏名) — it appears in the greeting line.' }}
                </p>
                <x-input-error :messages="$errors->get('japanese_name')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="department_name" value="Department（部署名）" />
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
            <x-input-label for="reason" value="Reason（理由）" />
            <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full"
                          x-model="reason" placeholder="e.g. 体調不良 / 私用 / 通院" required />
            <p class="mt-1 text-xs text-gray-500">The closing verb adapts to the reason (e.g. 体調不良 → 休ませていただきます, 私用 → 取らせていただきます).</p>
            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
        </div>

        {{-- Mail delivery fields --}}
        <div class="rounded-lg border border-gray-200 p-4 space-y-4 bg-gray-50/60">
            <h3 class="text-sm font-semibold text-gray-700">Mail Settings</h3>

            <div>
                <x-input-label for="subject" value="Subject (auto)" />
                <input id="subject" name="subject" type="text" readonly x-bind:value="subject"
                       class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-600 shadow-sm text-sm" />
                <p class="mt-1 text-xs text-gray-500">Automatically set — <span class="font-mono">【DD-MM-YYYY】Request for Half Day Leave application of [English name]</span>.</p>
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="to_emails" value="To" />
                <x-text-input id="to_emails" name="to_emails" type="text" class="mt-1 block w-full"
                              :value="old('to_emails', is_array($half->to_emails) ? implode(', ', $half->to_emails) : $half->to_emails)"
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
                              :value="old('cc_emails', is_array($half->cc_emails) ? implode(', ', $half->cc_emails) : $half->cc_emails)"
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
