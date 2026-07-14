<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Departments</h2>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('departments.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent md-ripple rounded font-medium text-sm text-white uppercase tracking-wider shadow-md-1 hover:bg-indigo-700 hover:shadow-md-2">
                    + New Department
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8"
             x-data="{
                open: false, title: '', formId: null,
                ask(id, name) { this.formId = id; this.title = name; this.open = true; },
                proceed() { this.open = false; if (this.formId) document.getElementById(this.formId).submit(); },
             }"
             @keydown.escape.window="open = false">
            @include('partials.flash')

            <div class="bg-white overflow-hidden shadow-md-1 sm:rounded-lg">
                @php ($isAdmin = auth()->user()->isAdmin())
                @if ($departments->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        No departments yet.
                        @if ($isAdmin)
                            <a href="{{ route('departments.create') }}" class="text-indigo-600 hover:underline">Create one</a>.
                        @endif
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Department</th>
                                @if ($isAdmin)
                                    <th class="px-6 py-3 text-right">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($departments as $department)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ $department->name }}</td>
                                    @if ($isAdmin)
                                        <td class="px-6 py-3 text-right space-x-3">
                                            <a href="{{ route('departments.edit', $department) }}" class="text-indigo-600 hover:underline">Edit</a>
                                            <button type="button" @click="ask('del-{{ $department->id }}', @js($department->name))"
                                                    class="text-red-600 hover:underline">Delete</button>
                                            <form id="del-{{ $department->id }}" method="POST" action="{{ route('departments.destroy', $department) }}" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="px-6 py-4">
                        {{ $departments->links() }}
                    </div>
                @endif
            </div>

            {{-- Delete confirmation modal --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
                <div class="absolute inset-0 bg-gray-900/50" @click="open = false"></div>
                <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl" x-transition.scale.origin.center @click.outside="open = false">
                    <div class="flex items-start gap-4 px-6 pt-6 pb-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900">Delete Department</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Delete "<span class="font-medium" x-text="title"></span>"? This cannot be undone.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <button type="button" @click="open = false" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="button" @click="proceed()" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
