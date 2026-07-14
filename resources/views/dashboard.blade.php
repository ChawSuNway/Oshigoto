<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('partials.flash')

            {{-- Stat cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                @php
                    $cards = [
                        ['label' => 'Reports this month', 'value' => $metrics['reports_month'], 'sub' => $metrics['reports_total'].' total', 'color' => 'bg-teal-500', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
                        ['label' => 'Net hours this month', 'value' => rtrim(rtrim(number_format($metrics['hours_month'], 2), '0'), '.'), 'sub' => 'hrs logged', 'color' => 'bg-orange-500', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                        ['label' => 'Late notices', 'value' => $metrics['late'], 'sub' => 'submitted', 'color' => 'bg-purple-500', 'icon' => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z'],
                        ['label' => 'Early leaves', 'value' => $metrics['early'], 'sub' => 'submitted', 'color' => 'bg-sky-500', 'icon' => 'M8.25 9.75 12 6m0 0 3.75 3.75M12 6v12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ];
                @endphp
                @foreach ($cards as $c)
                    <div class="bg-white rounded-lg shadow-md-1 hover:shadow-md-2 transition p-5 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-500">{{ $c['label'] }}</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $c['value'] }}</p>
                            <p class="mt-1 text-xs text-gray-400">{{ $c['sub'] }}</p>
                        </div>
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg text-white shadow-md-1 {{ $c['color'] }}">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $c['icon'] }}" /></svg>
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- Highlight (gradient) cards + quick actions --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="rounded-lg shadow-md-2 p-6 text-white bg-gradient-to-br from-emerald-500 to-teal-600">
                    <p class="text-sm opacity-90">Drafts pending</p>
                    <p class="mt-2 text-4xl font-bold">{{ $metrics['drafts'] }}</p>
                    <p class="mt-1 text-sm opacity-90">reports not yet sent</p>
                    <a href="{{ route('reports.index') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium bg-white/20 hover:bg-white/30 rounded px-3 py-1.5 transition">
                        Review reports
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>

                <div class="rounded-lg shadow-md-2 p-6 text-white bg-gradient-to-br from-sky-500 to-indigo-600">
                    <p class="text-sm opacity-90">Reports this month</p>
                    <p class="mt-2 text-4xl font-bold">{{ $metrics['reports_month'] }}</p>
                    <p class="mt-1 text-sm opacity-90">{{ now()->format('F Y') }}</p>
                    <a href="{{ route('reports.monthly') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium bg-white/20 hover:bg-white/30 rounded px-3 py-1.5 transition">
                        Monthly export
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>

                {{-- Quick actions --}}
                <div class="bg-white rounded-lg shadow-md-1 p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Quick actions</h3>
                    <div class="space-y-2">
                        @unless (auth()->user()->isManager())
                            <a href="{{ route('reports.create') }}" class="md-ripple flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100">
                                <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                New Daily Report
                            </a>
                        @endunless
                        <a href="{{ route('late.create') }}" class="md-ripple flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100">
                            <svg class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Inform Late Coming
                        </a>
                        <a href="{{ route('early.create') }}" class="md-ripple flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100">
                            <svg class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75 12 6m0 0 3.75 3.75M12 6v12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Early Leave Application
                        </a>
                    </div>
                </div>
            </div>

            {{-- Recent reports --}}
            <div class="bg-white rounded-lg shadow-md-1 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Recent reports</h3>
                    <a href="{{ route('reports.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
                </div>
                @if ($recent->isEmpty())
                    <div class="p-8 text-center text-gray-500 text-sm">No reports yet.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">{{ auth()->user()->isManager() ? 'Author' : 'Manager' }}</th>
                                    <th class="px-6 py-3">Hours</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($recent as $report)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 font-medium text-gray-900">{{ $report->report_date->format('d-m-Y') }}</td>
                                        <td class="px-6 py-3 text-gray-700">{{ auth()->user()->isManager() ? $report->user_name : $report->manager_name }}</td>
                                        <td class="px-6 py-3 text-gray-700">{{ rtrim(rtrim(number_format($report->total_hours, 2), '0'), '.') }}</td>
                                        <td class="px-6 py-3">
                                            @if ($report->sent_at)
                                                <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Sent</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800">Draft</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <a href="{{ route('reports.show', $report) }}" class="text-indigo-600 hover:underline">Preview</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
