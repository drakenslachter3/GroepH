<x-guest-layout>
    <h1 class="text-2xl font-bold mb-6">
        {{ __('Bevestig je wachtwoord') }}
    </h1>

    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Dit is een beveiligd gedeelte van de applicatie. Bevestig je wachtwoord voordat je doorgaat.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Wachtwoord -->
        <div>
            <x-input-label for="password" :value="__('Wachtwoord')" />

            <x-text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                aria-describedby="password-error"
            />

            @if ($errors->has('password'))
                <div id="password-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
                    {{ $errors->first('password') }}
                </div>
            @endif
        </div>

        <!-- Bevestig knop -->
        <div class="flex justify-end mt-4">
            <x-primary-button type="submit">
                {{ __('Bevestigen') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
