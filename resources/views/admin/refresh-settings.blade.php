<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
            {{ __('Verversinstellingen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-green-100 border border-green-500 text-green-800 px-4 py-3 rounded mb-6 dark:bg-green-900/30 dark:text-green-100 dark:border-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Verversen van dashboard</h3>

                    <form action="{{ route('admin.refresh-settings.update') }}" method="POST">
                        @csrf

                        <div class="mb-8">
                            <h4 class="text-md font-medium mb-3 text-gray-800 dark:text-gray-200">Globale instellingen</h4>
                            <div class="bg-gray-100 p-4 rounded-lg dark:bg-gray-700">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Functie</label>
                                        <div class="mt-1 p-2 bg-gray-200 rounded dark:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium">Dashboard verversing</div>
                                    </div>
                                    <div>
                                        <label for="refresh_interval" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Verversfrequentie</label>
                                        <select name="refresh_interval" id="refresh_interval"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                                            <option value="0" {{ $refreshInterval == 0 ? 'selected' : '' }}>Handmatig</option>
                                            <option value="300" {{ $refreshInterval == 300 ? 'selected' : '' }}>5 minuten</option>
                                            <option value="900" {{ $refreshInterval == 900 ? 'selected' : '' }}>15 minuten</option>
                                            <option value="1800" {{ $refreshInterval == 1800 ? 'selected' : '' }}>30 minuten</option>
                                            <option value="3600" {{ $refreshInterval == 3600 ? 'selected' : '' }}>1 uur</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Peak Hours Settings -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium mb-3 text-gray-800 dark:text-gray-200">Spitsuur instellingen</h4>
                            <div class="bg-gray-100 p-4 rounded-lg dark:bg-gray-700">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Verversen tijdens spitsuren (18:00-22:00)
                                        </label>
                                        <div class="flex items-center">
                                            <input type="hidden" name="allow_peak_hours_refresh" value="0">
                                            <input type="checkbox"
                                                   name="allow_peak_hours_refresh"
                                                   id="allow_peak_hours_refresh"
                                                   value="1"
                                                   {{ $allowPeakHoursRefresh ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                                            <label for="allow_peak_hours_refresh" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                Sta automatisch verversen toe tijdens spitsuren
                                            </label>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Wanneer uitgeschakeld wordt automatisch verversen geblokkeerd tussen 18:00-22:00 (Amsterdam tijd)
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                        <div class="mt-1 p-2 rounded flex items-center
                                            {{ $allowPeakHoursRefresh
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200'
                                                : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200' }}">
                                            <div class="w-2 h-2 rounded-full mr-2
                                                {{ $allowPeakHoursRefresh ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
                                            {{ $allowPeakHoursRefresh ? 'Actief tijdens spitsuren' : 'Geblokkeerd tijdens spitsuren' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-400 transition-colors">
                                Instellingen opslaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
