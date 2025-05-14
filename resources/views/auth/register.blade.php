<x-guest-layout>

    <!-- Skiplink -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 bg-white text-blue-600 px-4 py-2 z-50">
        Ga naar hoofdinhoud
    </a>

    <main id="main-content">
        <h1 class="text-2xl font-bold mb-6">
            {{ __('Registreren') }}
        </h1>

        <form method="POST" action="{{ route('register') }}" novalidate>
            @csrf

            <!-- Naam -->
            <div>
                <x-etc.input-label for="name" :value="__('Naam')" />
                <x-etc.text-input
                    id="name"
                    name="name"
                    type="text"
                    class="block mt-1 w-full"
                    :value="old('name')"
                    required
                    autofocus
                    autocomplete="name"
                    aria-describedby="{{ $errors->has('name') ? 'name-error' : '' }}"
                />
                @if ($errors->has('name'))
                    <div id="name-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
                        {{ $errors->first('name') }}
                    </div>
                @endif
            </div>

            <!-- E-mailadres -->
            <div class="mt-4">
                <x-etc.input-label for="email" :value="__('E-mailadres')" />
                <x-etc.text-input
                    id="email"
                    name="email"
                    type="email"
                    class="block mt-1 w-full"
                    :value="old('email')"
                    required
                    autocomplete="username"
                    aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                />
                @if ($errors->has('email'))
                    <div id="email-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
                        {{ $errors->first('email') }}
                    </div>
                @endif
            </div>

            <!-- Wachtwoord -->
            <div class="mt-4">
                <x-etc.input-label for="password" :value="__('Wachtwoord')" />
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

            <!-- Wachtwoord bevestigen -->
            <div class="mt-4">
                <x-etc.input-label for="password_confirmation" :value="__('Wachtwoord bevestigen')" />
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

            <!-- Spacing -->
            <div class="mt-4">&nbsp;</div>

            <!-- Acties -->
            <div class="flex items-center justify-between mt-4 mb-4">
                <x-primary-button type="submit">
                    {{ __('Registreren') }}
                </x-primary-button>

                <a href="{{ route('login') }}"
                   class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                    {{ __('Bent u al geregistreerd?') }}
                </a>
            </div>
        </form>
    </main>
</x-guest-layout>
