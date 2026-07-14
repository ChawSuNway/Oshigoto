<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Leave Application — {{ $leave->dateLabel() }}
            </h2>
            <a href="{{ route('leave.show', $leave) }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to preview</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash')
            @include('leave._form', [
                'action'      => route('leave.update', $leave),
                'method'      => 'PUT',
                'submitLabel' => 'Update Application',
                'cancelUrl'   => route('leave.show', $leave),
            ])
        </div>
    </div>
</x-app-layout>
