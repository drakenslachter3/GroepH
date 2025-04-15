<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Create a new secure password for your account.') }}
    </div>

    <form method="POST" action="{{ route('password.reset.update') }}">
        @csrf
        
        <!-- Hidden Token and Email -->
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <!-- Email Address (readonly) -->
        <div>
            <x-input-label for="email_display" :value="__('Email')" />
            <x-text-input id="email_display" class="block mt-1 w-full bg-gray-100 dark:bg-gray-700" type="email" name="email_display" :value="$email" disabled readonly />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>