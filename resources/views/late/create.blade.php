<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inform Late Coming</h2>
            <a href="{{ route('late.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to list</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash')

            <div class="bg-white shadow-md-1 sm:rounded-lg p-6"
                 x-data="{
                    englishName: @js(old('english_name', $late->english_name)),
                    japaneseName: @js(old('japanese_name', $late->japanese_name)),
                    reason: @js(old('reason', $late->reason)),
                    minutes: @js((string) old('minutes', $late->minutes)),
                    get subject() { return 'Inform Late Coming in office_' + (this.englishName || '[English name]'); },
                    get body() {
                        const t = (this.minutes || '0') + '分';
                        return '宛先各位\n\nお疲れ様です。' + (this.japaneseName || '[name]') + 'です。\n'
                            + (this.reason || '[reason]') + 'で' + t + '程遅れて出社しました。\n\nよろしくお願いいたします。';
                    },
                 }">
                <form method="POST" action="{{ route('late.store') }}" class="space-y-6" data-validate>
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="notice_date" value="Date" />
                            <x-text-input id="notice_date" name="notice_date" type="date" class="mt-1 block w-full"
                                          :value="old('notice_date', optional($late->notice_date)->format('Y-m-d') ?: now()->toDateString())" required />
                            <x-input-error :messages="$errors->get('notice_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="minutes" value="Late by (minutes)" />
                            <x-text-input id="minutes" name="minutes" type="number" min="1" max="1440" class="mt-1 block w-full"
                                          x-model="minutes" required />
                            <x-input-error :messages="$errors->get('minutes')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="english_name" value="English Name" />
                            <x-text-input id="english_name" name="english_name" type="text" class="mt-1 block w-full"
                                          x-model="englishName" required />
                            <x-input-error :messages="$errors->get('english_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="japanese_name" value="Japanese Name (氏名)" />
                            <x-text-input id="japanese_name" name="japanese_name" type="text" class="mt-1 block w-full"
                                          x-model="japaneseName" required />
                            <x-input-error :messages="$errors->get('japanese_name')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="reason" value="Reason（理由）" />
                        <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full"
                                      x-model="reason" placeholder="e.g. 電車遅延" required />
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                    </div>

                    {{-- Mail delivery fields --}}
                    <div class="rounded-lg border border-gray-200 p-4 space-y-4 bg-gray-50/60">
                        <h3 class="text-sm font-semibold text-gray-700">Mail Settings</h3>

                        <div>
                            <x-input-label for="subject" value="Subject (auto)" />
                            <input id="subject" name="subject" type="text" readonly x-bind:value="subject"
                                   class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-600 shadow-sm text-sm" />
                            <p class="mt-1 text-xs text-gray-500">Automatically set from the English name — <span class="font-mono">Inform Late Coming in office_[English name]</span>.</p>
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="to_emails" value="To" />
                            <x-text-input id="to_emails" name="to_emails" type="text" class="mt-1 block w-full"
                                          :value="old('to_emails', is_array($late->to_emails) ? implode(', ', $late->to_emails) : $late->to_emails)"
                                          placeholder="e.g. manager@example.com, teamlead@example.com" />
                            <p class="mt-1 text-xs text-gray-500">Separate multiple addresses with commas. Leave blank to send to your manager.</p>
                            <x-input-error :messages="$errors->get('to_emails')" class="mt-2" />
                            @foreach ($errors->get('to_emails.*') as $toErrors)
                                <x-input-error :messages="$toErrors" class="mt-2" />
                            @endforeach
                        </div>

                        <div>
                            <x-input-label for="cc_emails" value="CC (optional)" />
                            <x-text-input id="cc_emails" name="cc_emails" type="text" class="mt-1 block w-full"
                                          :value="old('cc_emails', is_array($late->cc_emails) ? implode(', ', $late->cc_emails) : $late->cc_emails)"
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
                        <x-primary-button>Save Notice</x-primary-button>
                        <a href="{{ route('late.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
