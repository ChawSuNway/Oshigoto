<div class="space-y-6" x-data="{ role: '{{ old('role', $user->role) }}' }">
    <div>
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="role" value="Role" />
        <select id="role" name="role" x-model="role"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="employee">Employee (writes reports)</option>
            <option value="manager">Manager (receives reports)</option>
            <option value="admin">Admin (manages users)</option>
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>

    <div x-show="role === 'employee'" x-cloak>
        <x-input-label for="manager_id" value="Reports To (Manager)" />
        <select id="manager_id" name="manager_id"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— None —</option>
            @foreach ($managers as $m)
                <option value="{{ $m->id }}" @selected(old('manager_id', $user->manager_id) == $m->id)>{{ $m->name }} ({{ $m->email }})</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('manager_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password" :value="$user->exists ? 'New Password (leave blank to keep current)' : 'Password'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password_confirmation" value="Confirm Password" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
    </div>

    <div class="flex items-center gap-4 pt-4 border-t">
        <x-primary-button>{{ $submitLabel ?? 'Save' }}</x-primary-button>
        <a href="{{ route('users.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
    </div>
</div>
