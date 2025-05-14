<x-guest-layout>

    <!-- Skiplink -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 bg-white text-blue-600 px-4 py-2 z-50">
        Ga naar hoofdinhoud
    </a>

    <main id="main-content">

        <h1 class="text-2xl font-bold mb-6">
            {{ __('Wachtwoord reset aanvragen') }}
        </h1>

        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
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

            <!-- Aanvraagknop -->
            <div class="flex items-center justify-end mt-4">
                <x-primary-button type="submit">
                    {{ __('Vraag aan') }}
                </x-primary-button>
            </div>
        </form>

    </main>
</x-guest-layout>
