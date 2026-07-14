<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leave Application</h2>
            <a href="{{ route('leave.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to list</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash')
            @include('leave._form', [
                'action'      => route('leave.store'),
                'method'      => 'POST',
                'submitLabel' => 'Save Application',
                'cancelUrl'   => route('leave.index'),
            ])
        </div>
    </div>
</x-app-layout>
