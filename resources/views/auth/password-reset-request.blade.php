<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Request a password reset. Your request will be reviewed by an administrator.') }}
    </div>

    <!-- Session Status -->
    <x-etc.auth-session-status class="mb-4" :status="session('status')" />

    <!-- Error Message -->
    @if(session('error'))
        <div class="mb-4 font-medium text-sm text-red-600 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email.request') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-etc.input-label for="email" :value="__('Email')" />
            <x-etc.text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-etc.input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Request Password Reset') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>