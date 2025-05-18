<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-etc.input-label for="name" :value="__('Naam')" />
            <x-etc.text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-etc.input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-etc.input-label for="email" :value="__('E-mailadres')" />
            <x-etc.text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-etc.input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-etc.input-label for="password" :value="__('Wachtwoord')" />

            <x-etc.text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Het wachtwoord moet minstens 8 tekens lang zijn en bestaan uit:') }}
            </p>
            <ul class="mt-1 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
                <li>{{ __('Eén hoofdletter') }}</li>
                <li>{{ __('Eén kleine letter') }}</li>
                <li>{{ __('Eén cijfer') }}</li>
                <li>{{ __('Eén speciaal teken') }}</li>
            </ul>

            <x-etc.input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-etc.input-label for="password_confirmation" :value="__(' Wachtwoord bevestigen')" />

            <x-etc.text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-etc.input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4">&nbsp;</div>

        <div class="flex items-center justify-between mt-4 mb-4">
            <x-primary-button>
                {{ __('Registreren') }}
            </x-primary-button>

            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Bent u al geregistreerd?') }}
            </a>
        </div>
    </form>
</x-guest-layout>
