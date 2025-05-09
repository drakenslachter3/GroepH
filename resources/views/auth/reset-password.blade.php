<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Maak een nieuw wachtwoord aan.') }}
    </div>

    <form method="POST" action="{{ route('password.reset.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div>
            <x-etc.input-label for="email_display" :value="__('Email')" />
            <x-etc.text-input id="email_display" class="block mt-1 w-full bg-gray-100 dark:bg-gray-700" type="email" name="email_display" :value="$email" disabled readonly />
        </div>

        <div class="mt-4">
            <x-etc.input-label for="password" :value="__('Nieuw wachtwoord')" />
            <x-etc.text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />

            <ul class="mt-2 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
                <li>Minstens 8 karakters lang</li>
                <li>Bevat minstens één hoofdletter</li>
                <li>Bevat minstens één kleine letter</li>
                <li>Bevat minstens één nummer</li>
                <li>Bevat minstens één speciaal karakter</li>
            </ul>

            <x-etc.input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-etc.input-label for="password_confirmation" :value="__('Herhaal wachtwoord')" />
            <x-etc.text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-etc.input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Reset Wachtwoord') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
