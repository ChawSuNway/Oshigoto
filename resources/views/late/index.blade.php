<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Late Coming Notices</h2>
            <a href="{{ route('late.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent md-ripple rounded font-medium text-sm text-white uppercase tracking-wider shadow-md-1 hover:bg-indigo-700 hover:shadow-md-2">
                + New Late Notice
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash')

            <div class="bg-white overflow-hidden shadow-md-1 sm:rounded-lg">
                @if ($notices->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        No late-coming notices yet.
                        <a href="{{ route('late.create') }}" class="text-indigo-600 hover:underline">Create one</a>.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Reason</th>
                                <th class="px-6 py-3">Late (min)</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($notices as $notice)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $notice->notice_date->format('d-m-Y') }}</td>
                                    <td class="px-6 py-3 text-gray-700">{{ $notice->reason }}</td>
                                    <td class="px-6 py-3 text-gray-700">{{ $notice->minutes }}分</td>
                                    <td class="px-6 py-3">
                                        @if ($notice->sent_at)
                                            <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Sent</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800">Draft</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right space-x-3">
                                        <a href="{{ route('late.show', $notice) }}" class="text-indigo-600 hover:underline">Preview</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="px-6 py-4">
                        {{ $notices->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
