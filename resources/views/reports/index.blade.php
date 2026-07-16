@php ($isAdmin = auth()->user()->isAdmin())

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                @if ($isAdmin)
                    Daily Work Reports — All Users
                @elseif (auth()->user()->isManager())
                    Reports From My Team
                @else
                    My Daily Work Reports
                @endif
            </h2>
            @unless (auth()->user()->isManager() || $isAdmin)
                <a href="{{ route('reports.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent md-ripple rounded font-medium text-sm text-white uppercase tracking-wider shadow-md-1 hover:bg-indigo-700 hover:shadow-md-2">
                    + New Daily Report
                </a>
            @endunless
        </div>
    </x-slot>

    <div class="py-8" x-data="{ tkdOpen: false, tkdTitle: '', tkdBody: '', copied: false,
                              openTkd(el) { this.tkdTitle = el.dataset.date; this.tkdBody = el.dataset.tkd; this.copied = false; this.tkdOpen = true; },
                              copyTkd() { navigator.clipboard.writeText(this.tkdBody).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1500); }); } }"
         @keydown.escape.window="tkdOpen = false">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash')

            <x-filter-bar :action="route('reports.index')" :users="$users" :departments="null" />

            <div class="bg-white overflow-hidden shadow-md-1 sm:rounded-lg">
                @if ($reports->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        No reports found.
                        @unless (auth()->user()->isManager() || $isAdmin)
                            <a href="{{ route('reports.create') }}" class="text-indigo-600 hover:underline">Create your first report</a>.
                        @endunless
                    </div>
                @else
                    <div class="px-6 pt-4 text-xs text-gray-500">{{ $reports->total() }} record(s)</div>

                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                @if ($isAdmin)
                                    <th class="px-6 py-3">Author</th>
                                    <th class="px-6 py-3">Manager</th>
                                @else
                                    <th class="px-6 py-3">{{ auth()->user()->isManager() ? 'Author' : 'Manager' }}</th>
                                @endif
                                <th class="px-6 py-3">Hours</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($reports as $report)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $report->report_date->format('d-m-Y') }}</td>
                                    @if ($isAdmin)
                                        <td class="px-6 py-3 text-gray-700">{{ $report->user_name ?: optional($report->user)->name }}</td>
                                        <td class="px-6 py-3 text-gray-700">{{ $report->manager_name ?: '—' }}</td>
                                    @else
                                        <td class="px-6 py-3 text-gray-700">
                                            {{ auth()->user()->isManager() ? $report->user_name : $report->manager_name }}
                                        </td>
                                    @endif
                                    <td class="px-6 py-3 text-gray-700">{{ rtrim(rtrim(number_format($report->total_hours, 2), '0'), '.') }}</td>
                                    <td class="px-6 py-3">
                                        @if ($report->sent_at)
                                            <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Sent</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800">Draft</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right space-x-3">
                                        <a href="{{ route('reports.show', $report) }}" class="text-indigo-600 hover:underline">Preview</a>
                                        <button type="button"
                                                data-date="{{ $report->report_date->format('d-m-Y') }}"
                                                data-tkd="{{ $report->renderTkdTemplate() }}"
                                                @click="openTkd($event.currentTarget)"
                                                class="text-emerald-600 hover:underline">TKD</button>
                                        @if ($report->user_id === auth()->id())
                                            <a href="{{ route('reports.edit', $report) }}" class="text-gray-600 hover:underline">Edit</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="px-6 py-4">
                        {{ $reports->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- TKD template modal --}}
        <div x-show="tkdOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition.opacity>
            <div class="absolute inset-0 bg-gray-900/50" @click="tkdOpen = false"></div>

            <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-xl"
                 x-transition.scale.origin.center
                 @click.outside="tkdOpen = false">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h3 class="font-semibold text-gray-800">
                        TKD Progress Report <span class="text-gray-400 font-normal" x-text="tkdTitle ? '— ' + tkdTitle : ''"></span>
                    </h3>
                    <button type="button" @click="tkdOpen = false"
                            class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="Close">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-5">
                    <pre class="max-h-[65vh] overflow-auto whitespace-pre-wrap rounded-md border border-gray-200 bg-gray-50 p-4 font-mono text-sm leading-relaxed text-gray-800" x-text="tkdBody"></pre>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                    <button type="button" @click="copyTkd()"
                            class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                    <button type="button" @click="tkdOpen = false"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
