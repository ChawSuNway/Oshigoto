<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Late Coming Notice — {{ $late->notice_date->format('d-m-Y') }}
            </h2>
            <a href="{{ route('late.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to list</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('partials.flash')

            {{-- Status line --}}
            <div class="bg-white shadow-md-1 sm:rounded-lg p-4 flex flex-wrap items-center gap-4 text-sm">
                <span><span class="text-gray-500">Reason:</span> <strong>{{ $late->reason }}</strong></span>
                <span><span class="text-gray-500">Late by:</span> <strong>{{ $late->minutes }}分</strong></span>
                @if ($late->sent_at)
                    <span class="ml-auto inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-green-800">
                        ✓ Sent {{ $late->sent_at->format('d-m-Y H:i') }}
                    </span>
                @else
                    <span class="ml-auto inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-yellow-800">Not sent yet</span>
                @endif
            </div>

            {{-- Mail delivery details --}}
            <div class="bg-white shadow-md-1 sm:rounded-lg p-4 text-sm space-y-1">
                <div><span class="text-gray-500 w-16 inline-block">Subject:</span> <strong>{{ $late->subjectLine() }}</strong></div>
                <div><span class="text-gray-500 w-16 inline-block">To:</span>
                    {{ !empty($late->to_emails) ? implode(', ', $late->to_emails) : (optional(auth()->user()->manager)->email ?? '— no recipient / manager —') }}
                </div>
                @if (!empty($late->cc_emails))
                    <div><span class="text-gray-500 w-16 inline-block">CC:</span> {{ implode(', ', $late->cc_emails) }}</div>
                @endif
            </div>

            {{-- Rendered template --}}
            <div class="bg-white shadow-md-1 sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800">Late Notice Template</h3>
                    <button type="button" onclick="copyTemplate()" class="text-sm text-indigo-600 hover:text-indigo-800">Copy to clipboard</button>
                </div>
                <pre id="tpl" class="whitespace-pre-wrap font-mono text-sm bg-gray-50 border border-gray-200 rounded-md p-4 text-gray-800 leading-relaxed">{{ $preview }}</pre>
            </div>

            {{-- Actions --}}
            <div x-data="{
                    open: false,
                    title: '',
                    message: '',
                    confirmLabel: 'OK',
                    variant: 'indigo',
                    formId: null,
                    sendMsg: @js('E-mail this late-coming notice to your manager?'),
                    ask(cfg) { Object.assign(this, cfg); this.open = true; },
                    proceed() { this.open = false; if (this.formId) document.getElementById(this.formId).submit(); },
                 }"
                 @keydown.escape.window="open = false">

                <div class="bg-white shadow-md-1 sm:rounded-lg p-4 flex flex-wrap items-center gap-3">
                    <form id="sendForm" method="POST" action="{{ route('late.send', $late) }}">
                        @csrf
                        <x-primary-button type="button"
                            @click="ask({ title: 'Send notice', message: sendMsg, confirmLabel: 'Send', variant: 'indigo', formId: 'sendForm' })">
                            {{ $late->sent_at ? 'Re-send to Manager' : 'Send to Manager' }}
                        </x-primary-button>
                    </form>

                    <form id="deleteForm" method="POST" action="{{ route('late.destroy', $late) }}" class="ml-auto">
                        @csrf
                        @method('DELETE')
                        <button type="button"
                                @click="ask({ title: 'Delete notice', message: 'Delete this late-coming notice permanently? This action cannot be undone.', confirmLabel: 'Delete', variant: 'red', formId: 'deleteForm' })"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent md-ripple rounded font-medium text-sm text-white uppercase tracking-wider shadow-md-1 hover:bg-red-700 hover:shadow-md-2">
                            Delete
                        </button>
                    </form>
                </div>

                {{-- Confirmation modal --}}
                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
                    <div class="absolute inset-0 bg-gray-900/50" @click="open = false"></div>

                    <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl"
                         x-transition.scale.origin.center @click.outside="open = false">
                        <div class="flex items-start gap-4 px-6 pt-6 pb-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                 :class="variant === 'red' ? 'bg-red-100 text-red-600' : 'bg-indigo-100 text-indigo-600'">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900" x-text="title"></h3>
                                <p class="mt-1 text-sm text-gray-600" x-text="message"></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                            <button type="button" @click="open = false"
                                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="button" @click="proceed()"
                                    class="rounded-md px-4 py-2 text-sm font-medium text-white shadow-sm"
                                    :class="variant === 'red' ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-700'">
                                <span x-text="confirmLabel"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyTemplate() {
            const text = document.getElementById('tpl').innerText;
            navigator.clipboard.writeText(text).then(() => window.showSnackbar('Template copied to clipboard'));
        }
    </script>
</x-app-layout>
