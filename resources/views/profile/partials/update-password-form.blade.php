<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Wachtwoord bijwerken') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Het wachtwoord moet minstens 8 tekens lang zijn en bestaan uit:') }}
        </p>
        <ul class="mt-1 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
            <li>{{ __('Eén hoofdletter') }}</li>
            <li>{{ __('Eén kleine letter') }}</li>
            <li>{{ __('Eén cijfer') }}</li>
            <li>{{ __('Eén speciaal teken') }}</li>
        </ul>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-etc.input-label for="update_password_current_password" :value="__('Huidig wachtwoord')" />
            <x-etc.text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-etc.input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-etc.input-label for="update_password_password" :value="__('Nieuw wachtwoord')" />
            <x-etc.text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-etc.input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-etc.input-label for="update_password_password_confirmation" :value="__('Bevestig wachtwoord')" />
            <x-etc.text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-etc.input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button dusk="save-button">{{ __('Opslaan') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Opgeslagen.') }}</p>
            @endif
        </div>
    </form>
</section>
