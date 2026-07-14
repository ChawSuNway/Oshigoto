<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Daily Work Report</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-md-1 sm:rounded-lg p-6">
                @include('partials.flash')

                <form method="POST" action="{{ route('reports.update', $report) }}" data-validate>
                    @csrf
                    @method('PUT')
                    @include('reports._form', ['submitLabel' => 'Update & Preview'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
