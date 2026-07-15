@php
    $departmentOptions = \App\Models\Department::orderBy('name')->pluck('name');
    // Lock the Japanese name only when the profile already has one.
    $jpLocked = filled(auth()->user()->japanese_name);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Early Leave Application</h2>
            <a href="{{ route('early.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to list</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash')

            <div class="bg-white shadow-md-1 sm:rounded-lg p-6"
                 x-data="{
                    englishName: @js(old('english_name', $early->english_name)),
                    japaneseName: @js(old('japanese_name', $early->japanese_name)),
                    department: @js(old('department_name', $departmentOptions->contains($early->department_name) ? $early->department_name : '')),
                    reason: @js(old('reason', $early->reason)),
                    leaveTime: @js(old('leave_time', $early->leave_time)),
                    get subject() { return 'Request for Early Leave application_' + (this.englishName || '[English name]'); },
                    get body() {
                        return '関係者各位\n\nお疲れ様です。' + (this.japaneseName || '[name]') + '@' + (this.department || '[department]') + 'です。\n\n'
                            + '今日は' + (this.reason || '[reason]') + '、' + (this.leaveTime || '[time]') + '時に早退させて頂きます。\n'
                            + 'お忙しいところ恐縮ですが、宜しくお願い致します。\n\n以上です。';
                    },
                 }">
                <form method="POST" action="{{ route('early.store') }}" class="space-y-6" data-validate>
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="notice_date" value="Date" />
                            <x-text-input id="notice_date" name="notice_date" type="date" class="mt-1 block w-full"
                                          :value="old('notice_date', optional($early->notice_date)->format('Y-m-d') ?: now()->toDateString())" required />
                            <x-input-error :messages="$errors->get('notice_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="leave_time" value="Leave time (hh:mm)" />
                            <x-text-input id="leave_time" name="leave_time" type="time" class="mt-1 block w-full"
                                          x-model="leaveTime" required />
                            <x-input-error :messages="$errors->get('leave_time')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="english_name" value="English Name" />
                            <x-text-input id="english_name" name="english_name" type="text" class="mt-1 block w-full"
                                          x-model="englishName" required />
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
                                      x-model="reason" placeholder="e.g. 通院のため" required />
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                    </div>

                    {{-- Mail delivery fields --}}
                    <div class="rounded-lg border border-gray-200 p-4 space-y-4 bg-gray-50/60">
                        <h3 class="text-sm font-semibold text-gray-700">Mail Settings</h3>

                        <div>
                            <x-input-label for="subject" value="Subject (auto)" />
                            <input id="subject" name="subject" type="text" readonly x-bind:value="subject"
                                   class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-600 shadow-sm text-sm" />
                            <p class="mt-1 text-xs text-gray-500">Automatically set from the English name — <span class="font-mono">Request for Early Leave application_[English name]</span>.</p>
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="to_emails" value="To" />
                            <x-text-input id="to_emails" name="to_emails" type="text" class="mt-1 block w-full"
                                          :value="old('to_emails', is_array($early->to_emails) ? implode(', ', $early->to_emails) : $early->to_emails)"
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
                                          :value="old('cc_emails', is_array($early->cc_emails) ? implode(', ', $early->cc_emails) : $early->cc_emails)"
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
                        <x-primary-button>Save Application</x-primary-button>
                        <a href="{{ route('early.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
