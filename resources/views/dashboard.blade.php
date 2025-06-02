<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
            {{ __('Energieverbruik Dashboard') }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div id="status-message"
                    class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-6 transition-opacity duration-1000 ease-out opacity-100">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-lg rounded-lg mb-8 dark:bg-gray-800">
                <div class="p-4">
                    <div class="flex justify-between items-center">
                        <button id="toggleConfigSection"
                            class="flex items-center text-left dark:text-white">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Dashboard Configuratie</h2>
                            <svg id="configSectionIcon" xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 ml-2 transform transition-transform duration-200" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Last update text and refresh button -->
                        <div class="flex items-center gap-3 dark:text-white">
                            <p class="text-sm text-gray-600 dark:text-white">Laatste update: <span
                                    id="last-updated">{{ $lastRefresh ?? 'Niet beschikbaar' }}</span></p>
                            <form action="{{ route('dashboard.refresh') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded transition duration-200 text-sm">
                                    Verversen
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Content section (collapsible) -->
                <div id="configSectionContent" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 border-t">
                        <!-- Widget Configuration Section -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-6 dark:text-white">Widget Configuratie</h2>
                            <form action="{{ route('dashboard.setWidget') }}" method="POST" class="space-y-6">
                                @csrf
                                <div class="space-y-2">
                                    <label for="grid-position"
                                        class="block text-sm font-medium text-gray-700 dark:text-white">Positie:</label>
                                    <select name="grid_position" id="grid-position"
                                        class="w-full p-3 bg-gray-50 rounded-md text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                                        aria-label="Selecteer de positie van de widget"
                                        aria-required="true">
                                        @for ($i = 0; $i < count($gridLayout); $i++)
                                            <option value="{{ $i }}" {{ old('grid_position') == $i ? 'selected' : '' }} aria-selected="{{ old('grid_position') == $i ? 'true' : 'false' }}">
                                                Positie {{ $i + 1 }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label for="widget-type"
                                        class="block text-sm font-medium text-gray-700 dark:text-white">Widget Type:</label>
                                        <select name="widget_type" class="w-full p-3 bg-gray-50 rounded-md text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            <option value="energy-status-electricity" {{ old('widget_type') == 'energy-status-electricity' ? 'selected' : '' }}>
                                                Electra Status
                                            </option>
                                            <option value="energy-status-gas" {{ old('widget_type') == 'energy-status-gas' ? 'selected' : '' }}>
                                                Gas Status
                                            </option>
                                            <option value="energy-chart-electricity" {{ old('widget_type') == 'energy-chart-electricity' ? 'selected' : '' }}>
                                                Electra Grafiek
                                            </option>
                                            <option value="energy-chart-gas" {{ old('widget_type') == 'energy-chart-gas' ? 'selected' : '' }}>
                                                Gas Grafiek
                                            </option>
                                            <option value="energy-suggestions" {{ old('widget_type') == 'energy-suggestions' ? 'selected' : '' }}>
                                                Energiebesparingstips
                                            </option>
                                            <option value="net-result" {{ old('widget_type') == 'net-result' ? 'selected' : '' }}>
                                                Netto resultante
                                            </option>
                                            <option value="energy-prediction-chart-electricity" {{ old('widget_type') == 'energy-prediction-chart-electricity' ? 'selected' : '' }}>
                                                Elektriciteit Voorspelling
                                            </option>
                                            <option value="energy-prediction-chart-gas" {{ old('widget_type') == 'energy-prediction-chart-gas' ? 'selected' : '' }}>
                                                Gas Voorspelling
                                            </option>
                                            <option value="budget-alert" {{ old('widget_type') == 'budget-alert' ? 'selected' : '' }}>
                                                Budget Waarschuwing
                                            </option>
                                            <option value="switch-meter" {{ old('widget_type') == 'switch-meter' ? 'selected' : '' }}>
                                                Selecteer meter
                                            </option>
                                        </select>
                                </div>

                                <button type="submit"
                                    class="w-full py-3 px-4 bg-green-500 hover:bg-green-600 text-white font-medium rounded-md shadow-sm transition duration-200 focus:ring-2 focus:ring-green-400 focus:ring-offset-2">
                                    Widget Toevoegen
                                </button>
                            </form>

                            <div class="mt-6 border-t border-gray-200 pt-6 flex space-x-4">
                                <form action="{{ route('dashboard.resetLayout') }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                        class="w-full py-2 px-4 bg-red-500 hover:bg-red-600 text-white font-medium rounded-md shadow-sm transition duration-200 focus:ring-2 focus:ring-red-400 focus:ring-offset-2">
                                        Reset Layout
                                    </button>
                                </form>

                                <button onclick="window.location.href='{{ route('budget.form') }}'"
                                    class="flex-1 py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:ring-2 focus:ring-purple-400 focus:ring-offset-2">
                                    Budget Aanpassen
                                </button>
                            </div>
                        </div>

                        <div>
                            <!-- Date Selector Header -->
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-6 mb-2">
                                {{ __('general.period_selection') }}
                            </h2>

                            <!-- Date Selector Section -->
                            <div role="group" aria-label="{{ __('general.period_selection') }}" class="flex justify-center mt-4">
                                @foreach (['day' => __('general.day'), 'month' => __('general.month'), 'year' => __('general.year')] as $key => $label)
                                    <form method="GET" action="{{ route('dashboard') }}" class="m-0 p-0">
                                        <input type="hidden" name="period" value="{{ $key }}">
                                        <input type="hidden" name="date" value="{{ $date }}">
                                        <input type="hidden" name="housing_type" value="{{ request('housing_type', 'tussenwoning') }}">
                                        <button 
                                            type="submit"
                                            class="flex items-center justify-center px-4 py-2 rounded-md mx-1 transition-colors
                                                {{ $period === $key 
                                                    ? 'bg-blue-500 text-white' 
                                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}"
                                            aria-pressed="{{ $period === $key ? 'true' : 'false' }}"
                                            aria-label="{{ __('general.show_data_per', ['period' => $label, 'current' => $period === $key ? __('general.current_setting') : '']) }}">
                                            {{ $label }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>

                            <!-- Date Picker Header -->
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-6 mb-2">
                                {{ __('general.select_date') }}
                            </h2>

                            <!-- Date Picker -->
                            <div class="date-input-container flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-2 mt-4">
                                @php
                                    $DatePickerClass = "w-full p-3 bg-gray-50 rounded-md dark:bg-gray-700 dark:text-gray-200 dark:[color-scheme:dark]";
                                @endphp

                                @switch($period)
                                    @case('day')
                                        <input type="date" 
                                            id="datePicker" 
                                            name="date" 
                                            class="{{ $DatePickerClass }}"
                                            value="{{ $date }}"
                                            aria-label="{{ __('general.select_date') }}">
                                        @break

                                    @case('month')
                                        <input type="month" 
                                            id="datePicker" 
                                            name="date" 
                                            class="{{ $DatePickerClass }}"
                                            value="{{ \Carbon\Carbon::parse($date)->format('Y-m') }}"
                                            aria-label="{{ __('general.select_month') }}">
                                        @break

                                    @case('year')
                                        <input type="number" 
                                            id="datePicker" 
                                            name="date" 
                                            class="{{ $DatePickerClass }}"
                                            value="{{ \Carbon\Carbon::parse($date)->format('Y') }}"
                                            min="2000" max="2050"
                                            aria-label="{{ __('general.select_year') }}">
                                        @break
                                @endswitch
                            </div>

                            <!-- Date Navigation Header -->
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-6 mb-2">
                                {{ __('general.date_navigation') }}
                            </h2>

                            <!-- Date Navigation Controls -->
                            <div role="group" aria-label="{{ __('general.date_navigation') }}" class="flex justify-center mt-4">
                                <form method="GET" action="{{ route('dashboard') }}" class="flex justify-center mt-4" role="group" aria-label="{{ __('general.date_navigation') }}">
                                    <input type="hidden" name="period" value="{{ $period }}">
                                    <input type="hidden" name="housing_type" value="{{ $housingType }}">
                                    <input type="hidden" id="date-input" name="date" value="">

                                    <!-- Previous Period Button -->
                                    <button type="submit"
                                            class="flex items-center justify-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 rounded-md mx-1 transition-colors"
                                            onclick="document.getElementById('date-input').value = '{{ \Carbon\Carbon::parse($date)->sub(1, $period)->format('Y-m-d') }}'"
                                            aria-label="{{ __('general.go_to_previous', ['period' => __('general.' . $period)]) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>

                                    <!-- Today Button -->
                                    <button type="submit"
                                            class="flex items-center justify-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 transition-colors mx-1 rounded-md"
                                            onclick="document.getElementById('date-input').value = '{{ \Carbon\Carbon::now()->format('Y-m-d') }}'"
                                            aria-label="{{ __('general.go_to_today') }}">
                                        {{ __('general.today') }}
                                    </button>

                                    <!-- Next Period Button -->
                                    <button type="submit"
                                            class="flex items-center justify-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 rounded-md mx-1 transition-colors"
                                            onclick="document.getElementById('date-input').value = '{{ \Carbon\Carbon::parse($date)->add(1, $period)->format('Y-m-d') }}'"
                                            aria-label="{{ __('general.go_to_next', ['period' => __('general.' . $period)]) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap -mx-2 justify-between">
                @foreach ($gridLayout as $item)
                    @php
                        // Skip the date-selector since we've integrated it into the top section
                        if ($item === 'date-selector') {
                            continue;
                        }

                        $widgetSize = match ($item) {
                            'usage-prediction' => 'large',
                            'energy-status-electricity', 'energy-status-gas' => 'small',
                            'energy-chart-electricity', 'energy-chart-gas' => 'large',
                            'energy-suggestions' => 'large',
                            'energy-prediction-chart-electricity', 'energy-prediction-chart-gas' => 'large',
                            'switch-meter' => 'full',
                            'net-result' => 'large',
                            default => 'full',
                        };

                        $widthClasses = match ($widgetSize) {
                            'small' => 'w-full sm:w-1/2 lg:w-1/4',
                            'medium' => 'w-full sm:w-1/2 lg:w-1/3',
                            'large' => 'w-full lg:w-1/2',
                            'full' => 'w-full',
                        };
                    @endphp

                    <div class="p-2 {{ $widthClasses }}">
                        <div class="h-full p-4 bg-white shadow-md rounded-lg dark:bg-gray-800 dark:text-white">
                            @switch($item)
                                @case('switch-meter')
                                    <x-dashboard.switch-meter title="Selecteer een meter"
                                    :meters="\App\Models\SmartMeter::getAllSmartMetersForCurrentUser()" 
                                    :selectedMeterId="\App\Models\UserGridLayout::getSelectedSmartMeterForCurrentUser()" />
                                @break

                                @case('energy-status-electricity')
                                    <x-dashboard.energy-status type="electricity" title="Status Elektriciteitsverbruik"
                                        :period="$period" :date="$date" unit="kWh"
                                        :usage="$liveData['electricity']['usage'] ?? 0" :target="$liveData['electricity']['target'] ?? 0"
                                        :cost="$liveData['electricity']['cost'] ?? 0" :percentage="$liveData['electricity']['percentage'] ?? 0" 
                                        :status="$liveData['electricity']['status'] ?? 'goed'"
                                        :liveData="$liveData['electricity'] ?? null" />
                                @break

                                @case('energy-status-gas')
                                    <x-dashboard.energy-status type="gas" title="Status Gasverbruik"
                                        :period="$period" :date="$date" unit="m³"
                                        :usage="$liveData['gas']['usage'] ?? 0" :target="$liveData['gas']['target'] ?? 0"
                                        :cost="$liveData['gas']['cost'] ?? 0" :percentage="$liveData['gas']['percentage'] ?? 0" 
                                        :status="$liveData['gas']['status'] ?? 'goed'"
                                        :liveData="$liveData['gas'] ?? null" />
                                @break

                                @case('energy-chart-electricity')
                                    <x-dashboard.energy-chart type="electricity" title="Grafiek Elektriciteitsverbruik"
                                        :period="$period" :date="$date" unit="kWh"
                                        buttonLabel="Toon Vorig Jaar" buttonColor="blue" :chartData="$meterDataForPeriod['current_data'] ?? []"
                                        :previousYearData="$meterDataForPeriod['historical_data'] ?? []" />
                                @break

                                @case('energy-chart-gas')
                                    <x-dashboard.energy-chart type="gas" title="Grafiek Gasverbruik"
                                        :period="$period" :date="$date" unit="m³"
                                        buttonLabel="Toon Vorig Jaar" buttonColor="yellow" :chartData="$meterDataForPeriod['current_data'] ?? []"
                                        :previousYearData="$meterDataForPeriod['historical_data'] ?? []" />
                                @break

                                @case('energy-suggestions')
                                    <x-dashboard.energy-suggestions title="Gepersonaliseerde Energiebesparingstips"
                                        :usagePattern="$usagePattern ?? 'avond'" :housingType="$housingType" :season="date('n') >= 3 && date('n') <= 5
                                        ? 'lente'
                                        : (date('n') >= 6 && date('n') <= 8
                                            ? 'zomer'
                                            : (date('n') >= 9 && date('n') <= 11
                                                ? 'herfst'
                                                : 'winter'))" />
                                @break

                                @case('energy-prediction-chart-electricity')
    <x-dashboard.energy-prediction-chart type="electricity" title="Voorspelling Elektriciteitsverbruik"
        :period="$period" :date="$date" unit="kWh"
        :currentData="$predictionData['electricity'] ?? []" 
        :budgetData="$budgetData['electricity'] ?? []"
        :percentage="$predictionPercentage['electricity'] ?? 0" 
        :confidence="$predictionConfidence['electricity'] ?? 75"
        :yearlyConsumptionToDate="$yearlyConsumptionToDate['electricity'] ?? 0" 
        :dailyAverageConsumption="$dailyAverageConsumption['electricity'] ?? 0"
        :realMeterData="$meterDataForPeriod['current_data'] ?? []"
        :dataKey="'energy_consumed'" />
@break

@case('energy-prediction-chart-gas')
    <x-dashboard.energy-prediction-chart type="gas" title="Voorspelling Gasverbruik"
        :period="$period" :date="$date" unit="m³"
        :currentData="$predictionData['gas'] ?? []" 
        :budgetData="$budgetData['gas'] ?? []" 
        :percentage="$predictionPercentage['gas'] ?? 0" 
        :confidence="$predictionConfidence['gas'] ?? 75" 
        :yearlyConsumptionToDate="$yearlyConsumptionToDate['gas'] ?? 0" 
        :dailyAverageConsumption="$dailyAverageConsumption['gas'] ?? 0"
        :realMeterData="$meterDataForPeriod['current_data'] ?? []"
        :dataKey="'gas_delivered'" />
@break

                                @case('net-result')
                                    <x-dashboard.net-result 
                                        :date="$date"
                                        :period="$period"
                                        :energyConsumed="$meterDataForPeriod['current_data']['energy_consumed'] ?? []"
                                        :energyProduced="$meterDataForPeriod['current_data']['energy_produced'] ?? []"
                                    />
                                @break

                                @default
                                    <?php
                                    echo $item;
                                    ?>
                            @endswitch
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Add this at the bottom before closing the layout -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/widget-navigation.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuration section toggle
            const toggleBtn = document.getElementById('toggleConfigSection');
            const content = document.getElementById('configSectionContent');
            const icon = document.getElementById('configSectionIcon');

            if (toggleBtn && content && icon) {
                // Check saved state
                const isOpen = localStorage.getItem('configSectionOpen') === 'true';
                
                if (isOpen) {
                    content.classList.remove('hidden');
                    icon.classList.add('rotate-180');
                }

                toggleBtn.addEventListener('click', function() {
                    const isHidden = content.classList.contains('hidden');
                    
                    content.classList.toggle('hidden');
                    icon.classList.toggle('rotate-180');
                    
                    localStorage.setItem('configSectionOpen', isHidden);
                });
            }

            // Auto-hide status message
            const statusMsg = document.getElementById('status-message');
            if (statusMsg) {
                setTimeout(() => {
                    statusMsg.classList.add('opacity-0');
                    setTimeout(() => statusMsg.remove(), 1000);
                }, 5000);
            }

            // Date picker change handler
            const datePicker = document.getElementById('datePicker');
            if (datePicker) {
                datePicker.addEventListener('change', function() {
                    const form = document.createElement('form');
                    form.method = 'GET';
                    form.action = '{{ route("dashboard") }}';
                    
                    const inputs = [
                        { name: 'period', value: '{{ $period }}' },
                        { name: 'date', value: this.value },
                        { name: 'housing_type', value: '{{ request("housing_type", "tussenwoning") }}' }
                    ];
                    
                    inputs.forEach(input => {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = input.name;
                        hiddenInput.value = input.value;
                        form.appendChild(hiddenInput);
                    });
                    
                    document.body.appendChild(form);
                    form.submit();
                });
            }
        });
    </script>
    @stack('chart-scripts')
    @stack('trend-scripts')
    @stack('prediction-chart-scripts')
    @stack('scripts')
</x-app-layout>
