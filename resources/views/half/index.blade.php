@php ($isAdmin = auth()->user()->isAdmin())

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $isAdmin ? 'Half Day Leave Applications — All Users' : 'Half Day Leave Applications' }}
            </h2>
            @unless ($isAdmin)
                <a href="{{ route('half.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent md-ripple rounded font-medium text-sm text-white uppercase tracking-wider shadow-md-1 hover:bg-indigo-700 hover:shadow-md-2">
                    + New Half Day Leave
                </a>
            @endunless
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash')

            <x-filter-bar :action="route('half.index')" :users="$users" :departments="$departments" />

            <div class="bg-white overflow-hidden shadow-md-1 sm:rounded-lg">
                @if ($applications->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        No half-day-leave applications found.
                        @unless ($isAdmin)
                            <a href="{{ route('half.create') }}" class="text-indigo-600 hover:underline">Create one</a>.
                        @endunless
                    </div>
                @else
                    <div class="px-6 pt-4 text-xs text-gray-500">{{ $applications->total() }} record(s)</div>

                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                @if ($isAdmin)
                                    <th class="px-6 py-3">User</th>
                                    <th class="px-6 py-3">Department</th>
                                @endif
                                <th class="px-6 py-3">Reason</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($applications as $app)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $app->notice_date->format('d-m-Y') }}</td>
                                    @if ($isAdmin)
                                        <td class="px-6 py-3 text-gray-700">{{ optional($app->user)->name ?? '—' }}</td>
                                        <td class="px-6 py-3 text-gray-700">{{ $app->department_name }}</td>
                                    @endif
                                    <td class="px-6 py-3 text-gray-700">{{ $app->reason }}</td>
                                    <td class="px-6 py-3 text-gray-700">{{ $app->leave_type }}</td>
                                    <td class="px-6 py-3">
                                        @if ($app->sent_at)
                                            <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Sent</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800">Draft</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right space-x-3">
                                        <a href="{{ route('half.show', $app) }}" class="text-indigo-600 hover:underline">Preview</a>
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
