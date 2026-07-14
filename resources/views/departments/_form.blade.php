<div class="space-y-6">
    <div>
        <x-input-label for="name" value="Department Name（部署名）" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $department->name)" placeholder="e.g. 開発部" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4 pt-4 border-t">
        <x-primary-button>{{ $submitLabel ?? 'Save' }}</x-primary-button>
        <a href="{{ route('departments.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
    </div>
</div>
