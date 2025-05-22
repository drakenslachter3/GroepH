<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
            {{ __('Voorspellingsinstellingen') }}
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
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Marges voor voorspellingen</h3>
                    
                    <form action="{{ route('admin.prediction-settings.update') }}" method="POST">
                        @csrf
                        
                        <!-- Global Settings -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium mb-3 text-gray-800 dark:text-gray-200">Globale instellingen</h4>
                            <div class="bg-gray-100 p-4 rounded-lg dark:bg-gray-700">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periode</label>
                                        <div class="mt-1 p-2 bg-gray-200 rounded dark:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium">Alle periodes</div>
                                        <input type="hidden" name="settings[0][type]" value="global">
                                        <input type="hidden" name="settings[0][period]" value="all">
                                    </div>
                                    <div>
                                        <label for="global_best_case" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Best case marge (%)</label>
                                        <input type="number" name="settings[0][best_case_margin]" id="global_best_case" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                            value="{{ old('settings.0.best_case_margin', $globalSettings->where('period', 'all')->first()->best_case_margin ?? 10) }}"
                                            min="0" max="50" step="0.1">
                                    </div>
                                    <div>
                                        <label for="global_worst_case" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Worst case marge (%)</label>
                                        <input type="number" name="settings[0][worst_case_margin]" id="global_worst_case" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                                            value="{{ old('settings.0.worst_case_margin', $globalSettings->where('period', 'all')->first()->worst_case_margin ?? 15) }}"
                                            min="0" max="50" step="0.1">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Electricity Settings -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium mb-3 text-gray-800 dark:text-gray-200">Elektriciteit instellingen</h4>
                            <div class="space-y-4">
                                @foreach(['day' => 'Dag', 'month' => 'Maand', 'year' => 'Jaar'] as $periodKey => $periodName)
                                    <div class="bg-blue-50 p-4 rounded-lg dark:bg-blue-900/20">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periode</label>
                                                <div class="mt-1 p-2 bg-blue-100 rounded text-blue-800 dark:bg-blue-800/50 dark:text-blue-100 font-medium">{{ $periodName }}</div>
                                                <input type="hidden" name="settings[{{ $loop->index + 1 }}][type]" value="electricity">
                                                <input type="hidden" name="settings[{{ $loop->index + 1 }}][period]" value="{{ $periodKey }}">
                                            </div>
                                            <div>
                                                <label for="electricity_{{ $periodKey }}_best_case" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Best case marge (%)</label>
                                                <input type="number" name="settings[{{ $loop->index + 1 }}][best_case_margin]" id="electricity_{{ $periodKey }}_best_case" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                                    value="{{ old('settings.'.($loop->index + 1).'.best_case_margin', $electricitySettings->where('period', $periodKey)->first()->best_case_margin ?? 10) }}"
                                                    min="0" max="50" step="0.1">
                                            </div>
                                            <div>
                                                <label for="electricity_{{ $periodKey }}_worst_case" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Worst case marge (%)</label>
                                                <input type="number" name="settings[{{ $loop->index + 1 }}][worst_case_margin]" id="electricity_{{ $periodKey }}_worst_case" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                                    value="{{ old('settings.'.($loop->index + 1).'.worst_case_margin', $electricitySettings->where('period', $periodKey)->first()->worst_case_margin ?? 15) }}"
                                                    min="0" max="50" step="0.1">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Gas Settings -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium mb-3 text-gray-800 dark:text-gray-200">Gas instellingen</h4>
                            <div class="space-y-4">
                                @foreach(['day' => 'Dag', 'month' => 'Maand', 'year' => 'Jaar'] as $periodKey => $periodName)
                                    <div class="bg-yellow-50 p-4 rounded-lg dark:bg-yellow-900/20">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periode</label>
                                                <div class="mt-1 p-2 bg-yellow-100 rounded text-yellow-800 dark:bg-yellow-800/40 dark:text-yellow-100 font-medium">{{ $periodName }}</div>
                                                <input type="hidden" name="settings[{{ $loop->index + 4 }}][type]" value="gas">
                                                <input type="hidden" name="settings[{{ $loop->index + 4 }}][period]" value="{{ $periodKey }}">
                                            </div>
                                            <div>
                                                <label for="gas_{{ $periodKey }}_best_case" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Best case marge (%)</label>
                                                <input type="number" name="settings[{{ $loop->index + 4 }}][best_case_margin]" id="gas_{{ $periodKey }}_best_case" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:focus:border-yellow-400 dark:focus:ring-yellow-400"
                                                    value="{{ old('settings.'.($loop->index + 4).'.best_case_margin', $gasSettings->where('period', $periodKey)->first()->best_case_margin ?? 10) }}"
                                                    min="0" max="50" step="0.1">
                                            </div>
                                            <div>
                                                <label for="gas_{{ $periodKey }}_worst_case" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Worst case marge (%)</label>
                                                <input type="number" name="settings[{{ $loop->index + 4 }}][worst_case_margin]" id="gas_{{ $periodKey }}_worst_case" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:focus:border-yellow-400 dark:focus:ring-yellow-400"
                                                    value="{{ old('settings.'.($loop->index + 4).'.worst_case_margin', $gasSettings->where('period', $periodKey)->first()->worst_case_margin ?? 15) }}"
                                                    min="0" max="50" step="0.1">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
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