<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Early Leave Applications</h2>
            <a href="{{ route('early.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent md-ripple rounded font-medium text-sm text-white uppercase tracking-wider shadow-md-1 hover:bg-indigo-700 hover:shadow-md-2">
                + New Early Leave
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash')

            <div class="bg-white overflow-hidden shadow-md-1 sm:rounded-lg">
                @if ($applications->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        No early-leave applications yet.
                        <a href="{{ route('early.create') }}" class="text-indigo-600 hover:underline">Create one</a>.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Reason</th>
                                <th class="px-6 py-3">Leave time</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($applications as $app)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $app->notice_date->format('d-m-Y') }}</td>
                                    <td class="px-6 py-3 text-gray-700">{{ $app->reason }}</td>
                                    <td class="px-6 py-3 text-gray-700">{{ $app->leave_time }}</td>
                                    <td class="px-6 py-3">
                                        @if ($app->sent_at)
                                            <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Sent</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800">Draft</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right space-x-3">
                                        <a href="{{ route('early.show', $app) }}" class="text-indigo-600 hover:underline">Preview</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="px-6 py-4">
                        {{ $applications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
