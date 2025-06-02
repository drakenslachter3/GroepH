<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Notificatie-instellingen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @if (session('status'))
                        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('notifications.update-settings') }}" class="mt-6 space-y-6">
                        @csrf

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Notificatie-frequentie') }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __("Hoe vaak wil je energienotificaties ontvangen?") }}
                            </p>

                            <div class="mt-4 space-y-4">
                                <div class="flex items-center">
                                    <input id="daily" name="notification_frequency" type="radio" value="daily" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" {{ $frequency === 'daily' ? 'checked' : '' }}>
                                    <label for="daily" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Dagelijks') }}
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="weekly" name="notification_frequency" type="radio" value="weekly" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" {{ $frequency === 'weekly' ? 'checked' : '' }}>
                                    <label for="weekly" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Wekelijks') }}
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="monthly" name="notification_frequency" type="radio" value="monthly" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" {{ $frequency === 'monthly' ? 'checked' : '' }}>
                                    <label for="monthly" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Maandelijks') }}
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="never" name="notification_frequency" type="radio" value="never" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" {{ $frequency === 'never' ? 'checked' : '' }}>
                                    <label for="never" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Nooit') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Drempelwaarden') }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __("Wanneer moet je een notificatie ontvangen?") }}
                            </p>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="electricity_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Elektriciteitsdrempel (%)') }}
                                    </label>
                                    <div class="mt-1 flex items-center">
                                        <input type="range" id="electricity_threshold" name="electricity_threshold" min="1" max="50" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700" value="{{ old('electricity_threshold', $user->electricity_threshold ?? 10) }}">
                                        <span id="electricity_threshold_value" class="ml-3 min-w-[3rem] text-sm text-gray-700 dark:text-gray-300">{{ old('electricity_threshold', $user->electricity_threshold ?? 10) }}%</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __("Notificatie bij voorspelde overschrijding van dit percentage.") }}
                                    </p>
                                </div>

                                <div>
                                    <label for="gas_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Gasdrempel (%)') }}
                                    </label>
                                    <div class="mt-1 flex items-center">
                                        <input type="range" id="gas_threshold" name="gas_threshold" min="1" max="50" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700" value="{{ old('gas_threshold', $user->gas_threshold ?? 10) }}">
                                        <span id="gas_threshold_value" class="ml-3 min-w-[3rem] text-sm text-gray-700 dark:text-gray-300">{{ old('gas_threshold', $user->gas_threshold ?? 10) }}%</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __("Notificatie bij voorspelde overschrijding van dit percentage.") }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Notificatie-inhoud') }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __("Wat voor informatie wil je in je notificaties zien?") }}
                            </p>

                            <div class="mt-4 space-y-4">
                                <div class="flex items-center">
                                    <input id="include_suggestions" name="include_suggestions" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" {{ $user->include_suggestions ? 'checked' : '' }}>
                                    <label for="include_suggestions" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Toon besparingstips') }}
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="include_comparison" name="include_comparison" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" {{ $user->include_comparison ? 'checked' : '' }}>
                                    <label for="include_comparison" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Toon vergelijking met vorig jaar') }}
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="include_forecast" name="include_forecast" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" {{ $user->include_forecast ? 'checked' : '' }}>
                                    <label for="include_forecast" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Toon voorspelling voor komende periode') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button dusk="save-button">{{ __('Opslaan') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const electricityThreshold = document.getElementById('electricity_threshold');
            const electricityThresholdValue = document.getElementById('electricity_threshold_value');
            const gasThreshold = document.getElementById('gas_threshold');
            const gasThresholdValue = document.getElementById('gas_threshold_value');

            electricityThreshold.addEventListener('input', function() {
                electricityThresholdValue.textContent = this.value + '%';
            });

            gasThreshold.addEventListener('input', function() {
                gasThresholdValue.textContent = this.value + '%';
            });
        });
    </script>
    @endpush
</x-app-layout>
