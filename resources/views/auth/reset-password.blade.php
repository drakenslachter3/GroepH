<x-guest-layout>

    <!-- Skiplink -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 bg-white text-blue-600 px-4 py-2 z-50">
        Ga naar hoofdinhoud
    </a>

    <main id="main-content">
        <h1 class="text-2xl font-bold mb-6">
            {{ __('Nieuw wachtwoord instellen') }}
        </h1>

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Maak een nieuw wachtwoord aan.') }}
        </div>

        <form method="POST" action="{{ route('password.reset.update') }}" novalidate>
            @csrf

            <!-- Hidden inputs -->
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <!-- Email (readonly) -->
            <div>
                <x-etc.input-label for="email_display" :value="__('E-mailadres')" />
                <x-etc.text-input
                    id="email_display"
                    type="email"
                    name="email_display"
                    :value="$email"
                    class="block mt-1 w-full bg-gray-100 dark:bg-gray-700"
                    readonly
                    disabled
                    aria-describedby="email-hint"
                />
                <p id="email-hint" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Je e-mailadres kan niet worden gewijzigd.') }}
                </p>
            </div>

            <!-- Nieuw wachtwoord -->
            <div class="mt-4">
                <x-etc.input-label for="password" :value="__('Nieuw wachtwoord')" />
                <x-etc.text-input
                    id="password"
                    name="password"
                    type="password"
                    class="block mt-1 w-full"
                    required
                    autocomplete="new-password"
                    aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}"
                />
                @if ($errors->has('password'))
                    <div id="password-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
                        {{ $errors->first('password') }}
                    </div>
                @endif
            </div>

            <!-- Herhaal wachtwoord -->
            <div class="mt-4">
                <x-etc.input-label for="password_confirmation" :value="__('Herhaal wachtwoord')" />
                <x-etc.text-input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    class="block mt-1 w-full"
                    required
                    autocomplete="new-password"
                    aria-describedby="{{ $errors->has('password_confirmation') ? 'password-confirmation-error' : '' }}"
                />
                @if ($errors->has('password_confirmation'))
                    <div id="password-confirmation-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
                        {{ $errors->first('password_confirmation') }}
                    </div>
                @endif
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end mt-6">
                <x-primary-button type="submit">
                    {{ __('Reset wachtwoord') }}
                </x-primary-button>
            </div>
        </form>
    </main>
</x-guest-layout>
