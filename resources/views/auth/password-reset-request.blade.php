<x-guest-layout>
    <h1 tabindex="0" class="text-2xl font-bold mb-6 dark:text-white">
        {{ __('Wachtwoord reset aanvragen') }}
    </h1>

    <p tabindex="0" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Vraag een resetverzoek aan. Je verzoek moet worden geaccepteerd door een beheerder.') }}
    </p>

    <!-- Session Status -->
    <x-etc.auth-session-status class="mb-4" :status="session('status')" />

    <!-- Error Message (custom error) -->
    @if(session('error'))
        <div class="mb-4 text-sm text-red-600 dark:text-red-400" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email.request') }}" novalidate>
        @csrf

        <!-- E-mailadres -->
        <div>
            <x-etc.input-label for="email" :value="__('E-mailadres')" />

            <x-etc.text-input
                id="email"
                name="email"
                type="email"
                class="block mt-1 w-full"
                :value="old('email')"
                required
                autofocus
                autocomplete="email"
                aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
            />

            @if ($errors->has('email'))
                <div id="email-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
                    {{ $errors->first('email') }}
                </div>
            @endif
        </div>

        <!-- Flex container for link and button -->
        <div class="flex flex-row justify-between items-center flex-wrap gap-4 mt-4">
            
            <!-- Back to loginscreen -->
            <div>
                <a href="{{ route('login') }}" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">
                    {{ __('Terug naar inloggen') }}
                </a>   
            </div>

            <!-- Aanvraagknop -->
            <div>
                <x-primary-button type="submit">
                    {{ __('Vraag aan') }}
                </x-primary-button>
            </div>

        </div>
    </form>
</x-guest-layout>
