<x-guest-layout>
        <h1 tabindex="0" class="text-2xl font-bold mb-6 dark:text-white">
            {{ __('E-mailadres verifiëren') }}
        </h1>

        <div tabindex="0" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Bedankt voor je registratie! Klik op de link in de e-mail die we je hebben gestuurd om je e-mailadres te verifiëren. Heb je geen e-mail ontvangen? We sturen er graag nog een.') }}
        </div>

        @if (session('status') === 'verification-link-sent')
            <div tabindex="0" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400" role="status">
                {{ __('Er is een nieuwe verificatielink verzonden naar het e-mailadres dat je bij registratie hebt opgegeven.') }}
            </div>
        @endif

        <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Resend Verification -->
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-primary-button>
                    {{ __('Verificatie e-mail opnieuw verzenden') }}
                </x-primary-button>
            </form>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">
                    {{ __('Uitloggen') }}
                </button>
            </form>
        </div>
    </>

</x-guest-layout>
