<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit System ID — {{ $systemId->code }}</h2>
            <a href="{{ route('system-ids.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to list</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @include('partials.flash')
            <div class="bg-white shadow-md-1 sm:rounded-lg p-6">
                <form method="POST" action="{{ route('system-ids.update', $systemId) }}" data-validate>
                    @csrf
                    @method('PUT')
                    @include('system_ids._form', ['submitLabel' => 'Update System ID'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
