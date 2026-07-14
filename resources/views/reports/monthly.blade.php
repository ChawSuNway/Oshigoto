<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Monthly Report — <span class="text-gray-500">{{ $label }}</span>
            </h2>
            <a href="{{ route('reports.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to list</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('partials.flash')

            {{-- Month picker + export --}}
            <div class="bg-white shadow-md-1 sm:rounded-lg p-4">
                <form method="GET" action="{{ route('reports.monthly') }}"
                      class="flex flex-wrap items-end gap-4">
                    <div>
                        <label for="month" class="block text-xs font-medium text-gray-500 mb-1">Month</label>
                        <input type="month" id="month" name="month" value="{{ $monthValue }}"
                               onchange="this.form.submit()"
                               class="border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Show
                    </button>

                    <div class="ms-auto flex items-center gap-4">
                        <span class="text-sm text-gray-500">{{ $count }} {{ $count === 1 ? 'report' : 'reports' }} in this period</span>
                        <a href="{{ route('reports.monthly.export', ['month' => $monthValue]) }}"
                           @class([
                               'inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium text-white shadow-sm',
                               'bg-emerald-600 hover:bg-emerald-700' => $count > 0,
                               'bg-gray-300 pointer-events-none' => $count === 0,
                           ])>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            Export to Excel
                        </a>
                    </div>
                </form>
            </div>

            {{-- Preview grid (mirrors the exported sheet) --}}
            <div class="bg-white shadow-md-1 sm:rounded-lg overflow-hidden">
                @if (empty($rows))
                    <div class="p-8 text-center text-gray-500">
                        No reports for {{ $label }}.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-indigo-50 text-gray-700 text-xs">
                                    <th class="border border-gray-200 px-3 py-2 text-left whitespace-nowrap">日付</th>
                                    <th class="border border-gray-200 px-3 py-2 text-left whitespace-nowrap">システムID</th>
                                    <th class="border border-gray-200 px-3 py-2 text-left whitespace-nowrap">案件No</th>
                                    <th class="border border-gray-200 px-3 py-2 text-left whitespace-nowrap">時間外/電話対応</th>
                                    <th class="border border-gray-200 px-3 py-2 text-left whitespace-nowrap">時間（h）</th>
                                    <th class="border border-gray-200 px-3 py-2 text-left">作業(サギョウ)内容(ナイヨウ)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        @if ($row['first'])
                                            <td rowspan="{{ $row['rowspan'] }}" class="border border-gray-200 px-3 py-1.5 whitespace-nowrap text-gray-800 align-middle bg-gray-50/60">{{ $row['date'] }}</td>
                                            <td rowspan="{{ $row['rowspan'] }}" class="border border-gray-200 px-3 py-1.5 whitespace-nowrap text-gray-700 align-middle bg-gray-50/60">{{ $row['system_id'] }}</td>
                                            <td rowspan="{{ $row['rowspan'] }}" class="border border-gray-200 px-3 py-1.5 whitespace-nowrap text-gray-700 align-middle bg-gray-50/60">{{ $row['case_no'] }}</td>
                                            <td rowspan="{{ $row['rowspan'] }}" class="border border-gray-200 px-3 py-1.5 whitespace-nowrap text-gray-700 align-middle bg-gray-50/60">{{ $row['sst'] }}</td>
                                            <td rowspan="{{ $row['rowspan'] }}" class="border border-gray-200 px-3 py-1.5 whitespace-nowrap text-right text-gray-700 align-middle bg-gray-50/60">{{ $row['time_h'] === '' ? '' : number_format((float) $row['time_h'], 2) }}</td>
                                        @endif
                                        <td @class(['border border-gray-200 px-3 py-1.5 text-gray-800 align-middle', 'ps-8 text-gray-600' => $row['sub']])>{{ $row['content'] }}</td>
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
