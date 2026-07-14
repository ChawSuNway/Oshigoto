<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New User</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-md-1 sm:rounded-lg p-6">
                @include('partials.flash')
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    @include('users._form', ['submitLabel' => 'Create User'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
