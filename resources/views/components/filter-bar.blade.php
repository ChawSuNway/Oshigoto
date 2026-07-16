@props([
    'action',
    'users' => null,          // collection => render the admin "User" filter
    'departments' => null,    // collection => render the Department filter
    'showStatus' => true,
    'showSearch' => false,
    'dateLabel' => 'Date range',
    'searchPlaceholder' => 'Search...',
])

@php
    $hasFilters = collect(['user_id', 'department', 'from', 'to', 'status', 'q'])
        ->contains(fn ($k) => filled(request($k)));
@endphp

<div class="bg-white shadow-md-1 sm:rounded-lg p-4 mb-4">
    <form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3">

        @if ($users)
            <div class="min-w-[12rem]">
                <label for="f_user" class="block text-xs font-medium text-gray-500 mb-1">User</label>
                <select id="f_user" name="user_id"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($departments && $departments->isNotEmpty())
            <div class="min-w-[12rem]">
                <label for="f_dept" class="block text-xs font-medium text-gray-500 mb-1">Department</label>
                <select id="f_dept" name="department"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept }}" @selected(request('department') === $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label for="f_from" class="block text-xs font-medium text-gray-500 mb-1">{{ $dateLabel }}</label>
            <div class="flex items-center gap-2">
                <input id="f_from" type="date" name="from" value="{{ request('from') }}"
                       class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <span class="text-gray-400 text-sm">to</span>
                <input id="f_to" type="date" name="to" value="{{ request('to') }}"
                       class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>
        </div>

        @if ($showStatus)
            <div>
                <label for="f_status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select id="f_status" name="status"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Any</option>
                    <option value="sent" @selected(request('status') === 'sent')>Sent</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                </select>
            </div>
        @endif

        @if ($showSearch)
            <div class="min-w-[12rem]">
                <label for="f_q" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input id="f_q" type="text" name="q" value="{{ request('q') }}" placeholder="{{ $searchPlaceholder }}"
                       class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>
        @endif

        <div class="flex items-center gap-2">
            <button type="submit"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                Filter
            </button>
            @if ($hasFilters)
                <a href="{{ $action }}" class="text-sm text-gray-500 hover:text-gray-800">Reset</a>
            @endif
        </div>
    </form>
</div>
