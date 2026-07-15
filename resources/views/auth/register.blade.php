<x-guest-layout>
    <!-- Register with Google (creates the account and signs you straight in) -->
    <x-google-button label="Sign up with Google" />

    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="bg-white px-3 text-xs uppercase tracking-wider text-gray-400">or</span>
        </div>
    </div>

    <form method="POST" action="{{ route('register') }}" data-validate>
        @csrf

        <!-- English Name -->
        <div>
            <x-input-label for="name" :value="__('Name (English)')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="e.g. Yamada Taro" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Japanese Name -->
        <div class="mt-4">
            <x-input-label for="japanese_name" :value="__('Name (Japanese / 氏名)')" />
            <x-text-input id="japanese_name" class="block mt-1 w-full" type="text" name="japanese_name" :value="old('japanese_name')" required autocomplete="off" placeholder="例：山田太郎" />
            <x-input-error :messages="$errors->get('japanese_name')" class="mt-2" />
        </div>

        <!-- Department -->
        <div class="mt-4">
            <x-input-label for="department_name" :value="__('Department (部署名) — optional')" />
            <x-text-input id="department_name" class="block mt-1 w-full" type="text" name="department_name" :value="old('department_name')" autocomplete="off" placeholder="例：開発部" />
            <x-input-error :messages="$errors->get('department_name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
