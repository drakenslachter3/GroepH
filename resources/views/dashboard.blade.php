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
                <!-- Toggle button for the entire config section -->
                <div class="p-4">
                    <!-- Dropdown button, last update info & refresh button in one row -->
                    <div class="flex justify-between items-center">
                        <!-- Dropdown button with title and icon -->
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
                                        class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md text-gray-700"
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
                                        <select name="widget_type" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md text-gray-700">
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

                        <!-- Date Selector Section -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-6 dark:text-white">Datum en Periode</h2>

                            <!-- Current date/period display -->
                            <div class="mb-4 pb-4 border-b border-gray-200">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                                    @switch($period)
                                        @case('day')
                                            Energieverbruik op {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
                                        @break

                                        @case('month')
                                            Energieverbruik in {{ \Carbon\Carbon::parse($date)->format('F Y') }}
                                        @break

                                        @case('year')
                                            Energieverbruik in {{ \Carbon\Carbon::parse($date)->format('Y') }}
                                        @break

                                        @default
                                            Energieverbruik
                                    @endswitch
                                </h3>
                            </div>

                            <!-- Form for Time Settings -->
                            <form id="timeSetterForm" action="{{ route('dashboard.setTime') }}" method="POST"
                                class="space-y-6">
                                @csrf
                                <!-- Period selection -->
                                <div class="mb-4">
                                    <h3 class="text-lg font-medium mb-2 text-black dark:text-white">Tijdsperiode</h3>
                                    <div class="flex space-x-4" role="radiogroup" aria-label="Selecteer periode">
                                        <label class="inline-flex items-center" role="radio" aria-checked="{{ $period === 'day' ? 'true' : 'false' }}" tabindex="0">
                                            <input type="radio" name="period" value="day" {{ $period === 'day' ? 'checked' : '' }} class="sr-only">
                                            <span class="px-4 py-2 rounded-md cursor-pointer {{ $period === 'day' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                Dag
                                            </span>
                                        </label>
                                    
                                        <label class="inline-flex items-center" role="radio" aria-checked="{{ $period === 'month' ? 'true' : 'false' }}" tabindex="0">
                                            <input type="radio" name="period" value="month" {{ $period === 'month' ? 'checked' : '' }} class="sr-only">
                                            <span class="px-4 py-2 rounded-md cursor-pointer {{ $period === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                Maand
                                            </span>
                                        </label>
                                    
                                        <label class="inline-flex items-center" role="radio" aria-checked="{{ $period === 'year' ? 'true' : 'false' }}" tabindex="0">
                                            <input type="radio" name="period" value="year" {{ $period === 'year' ? 'checked' : '' }} class="sr-only">
                                            <span class="px-4 py-2 rounded-md cursor-pointer {{ $period === 'year' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                Jaar
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Date picker -->
                                <div class="mb-4">
                                    <h3 class="text-lg font-medium mb-2 dark:text-white">Datumkiezer</h3>
                                    <div class="flex items-center space-x-2">
                                        <div class="date-input-container w-full">
                                            @switch($period)
                                                @case('day')
                                                    <input type="date" name="date" id="datePicker"
                                                        class="date-picker w-full p-3 bg-gray-50 border border-gray-300 rounded-md"
                                                        value="{{ $date }}">
                                                @break

                                                @case('month')
                                                    <input type="month" name="date" id="datePicker"
                                                        class="date-picker w-full p-3 bg-gray-50 border border-gray-300 rounded-md"
                                                        value="{{ \Carbon\Carbon::parse($date)->format('Y-m') }}">
                                                @break

                                                @case('year')
                                                    <input type="number" name="date" id="datePicker"
                                                        class="date-picker w-full p-3 bg-gray-50 border border-gray-300 rounded-md"
                                                        value="{{ \Carbon\Carbon::parse($date)->format('Y') }}"
                                                        min="2000" max="2050">
                                                @break
                                            @endswitch
                                        </div>
                                    </div>

                                    <!-- Navigation arrows for date -->
                                    <div class="flex justify-center mt-4">
                                        <a href="{{ route('dashboard', ['period' => $period, 'date' => \Carbon\Carbon::parse($date)->sub(1, $period)->format('Y-m-d'), 'housing_type' => $housingType]) }}"
                                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-l-md">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('dashboard', ['period' => $period, 'date' => \Carbon\Carbon::now()->format('Y-m-d'), 'housing_type' => $housingType]) }}"
                                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 mx-1">
                                            Vandaag
                                        </a>
                                        <a href="{{ route('dashboard', ['period' => $period, 'date' => \Carbon\Carbon::parse($date)->add(1, $period)->format('Y-m-d'), 'housing_type' => $housingType]) }}"
                                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-r-md">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                                <!-- Housing type selection TODO -->
                                <div class="hidden">
                                    <h3 class="text-lg font-medium mb-2">Woningtype</h3>
                                    <select name="housing_type" id="housingType" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md">
                                        <option value="appartement" {{ $housingType === 'appartement' ? 'selected' : '' }}>Appartement</option>
                                        <option value="tussenwoning" {{ $housingType === 'tussenwoning' ? 'selected' : '' }}>Tussenwoning</option>
                                        <option value="hoekwoning" {{ $housingType === 'hoekwoning' ? 'selected' : '' }}>Hoekwoning</option>
                                        <option value="twee_onder_een_kap" {{ $housingType === 'twee_onder_een_kap' ? 'selected' : '' }}>2-onder-1-kap</option>
                                        <option value="vrijstaand" {{ $housingType === 'vrijstaand' ? 'selected' : '' }}>Vrijstaand</option>
                                    </select>
                                </div>
                            </form>
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
                                        :currentData="$predictionData['electricity'] ?? []" :budgetData="$budgetData['electricity'] ?? []"
                                        :percentage="$predictionPercentage['electricity'] ?? 0" :confidence="$predictionConfidence['electricity'] ?? 75"
                                        :yearlyConsumptionToDate="$yearlyConsumptionToDate['electricity'] ?? 0" :dailyAverageConsumption="$dailyAverageConsumption['electricity'] ?? 0" />
                                @break

                                @case('energy-prediction-chart-gas')
                                    <x-dashboard.energy-prediction-chart type="gas" title="Voorspelling Gasverbruik"
                                        :period="$period" :date="$date" unit="m³"
                                        :currentData="$predictionData['gas'] ?? []" :budgetData="$budgetData['gas'] ?? []" 
                                        :percentage="$predictionPercentage['gas'] ?? 0" :confidence="$predictionConfidence['gas'] ?? 75" 
                                        :yearlyConsumptionToDate="$yearlyConsumptionToDate['gas'] ?? 0" :dailyAverageConsumption="$dailyAverageConsumption['gas'] ?? 0" />
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
        // Define default values for period labels
        const periodLabels = {
            'day': 'Uren',
            'month': 'Dagen',
            'year': 'Maanden'
        };

        // Define default empty data structure if not provided by the backend
        const lastYearData = {
            electricity: [],
            gas: []
        };

        // Config section toggle and time setter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle for the entire configuration section
            const toggleConfigSection = document.getElementById('toggleConfigSection');
            const configSectionContent = document.getElementById('configSectionContent');
            const configSectionIcon = document.getElementById('configSectionIcon');

            if (toggleConfigSection && configSectionContent) {
                // Check localStorage for saved state
                const configSectionOpen = localStorage.getItem('configSectionOpen') === 'true';

                // Set initial state based on localStorage or default to open on first visit
                if (configSectionOpen || localStorage.getItem('configSectionOpen') === null) {
                    configSectionContent.classList.remove('hidden');
                    configSectionIcon.classList.add('rotate-180');
                }

                toggleConfigSection.addEventListener('click', function() {
                    configSectionContent.classList.toggle('hidden');
                    configSectionIcon.classList.toggle('rotate-180');

                    // Save state to localStorage
                    localStorage.setItem('configSectionOpen', !configSectionContent.classList.contains(
                        'hidden'));
                });
            }

            // Period selection UI enhancement
            const periodRadios = document.querySelectorAll('input[name="period"]');
            periodRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Reset all buttons
                    periodRadios.forEach(r => {
                        r.nextElementSibling.classList.remove('bg-blue-600', 'text-white');
                        r.nextElementSibling.classList.add('bg-gray-200', 'text-gray-700');
                    });

                    // Highlight the selected button
                    this.nextElementSibling.classList.remove('bg-gray-200', 'text-gray-700');
                    this.nextElementSibling.classList.add('bg-blue-600', 'text-white');

                    // Update date input type based on selected period
                    updateDatePickerType(this.value);

                    // Submit form after a short delay
                    setTimeout(function() {
                        document.getElementById('timeSetterForm').submit();
                    }, 300);
                });
            });

            // Function to update date picker type based on period
            function updateDatePickerType(period) {
                const dateContainer = document.querySelector('.date-input-container');
                let dateInput;

                if (period === 'day') {
                    dateInput =
                        `<input type="date" name="date" id="datePicker" class="date-picker w-full p-3 bg-gray-50 border border-gray-300 rounded-md" value="{{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}">`;
                } else if (period === 'month') {
                    dateInput =
                        `<input type="month" name="date" id="datePicker" class="date-picker w-full p-3 bg-gray-50 border border-gray-300 rounded-md" value="{{ \Carbon\Carbon::parse($date)->format('Y-m') }}">`;
                } else if (period === 'year') {
                    dateInput =
                        `<input type="number" name="date" id="datePicker" class="date-picker w-full p-3 bg-gray-50 border border-gray-300 rounded-md" value="{{ \Carbon\Carbon::parse($date)->format('Y') }}" min="2000" max="2050">`;
                }

                if (dateContainer) {
                    dateContainer.innerHTML = dateInput;

                    // Listen for changes on the new datepicker
                    const newDatePicker = document.getElementById('datePicker');
                    if (newDatePicker) {
                        newDatePicker.addEventListener('change', function() {
                            document.getElementById('timeSetterForm').submit();
                        });

                        // For month and year inputs that might not trigger change event
                        if (period === 'month' || period === 'year') {
                            newDatePicker.addEventListener('input', function() {
                                // Use a timer to prevent frequent submits
                                if (this._timer) clearTimeout(this._timer);
                                this._timer = setTimeout(() => {
                                    document.getElementById('timeSetterForm').submit();
                                }, 500);
                            });

                            // For year input, also listen for Enter key
                            if (period === 'year') {
                                newDatePicker.addEventListener('keydown', function(e) {
                                    if (e.key === 'Enter') {
                                        document.getElementById('timeSetterForm').submit();
                                    }
                                });
                            }
                        }
                    }
                }
            }

            // Initial setup of the datepicker listener
            function initDatePickerListener() {
                const datePicker = document.getElementById('datePicker');
                if (datePicker) {
                    datePicker.addEventListener('change', function() {
                        document.getElementById('timeSetterForm').submit();
                    });

                    // Voor type="month" en type="number" inputs
                    if (datePicker.type === 'month' || datePicker.type === 'number') {
                        datePicker.addEventListener('input', function() {
                            // Vertraging om te voorkomen dat het formulier te vaak wordt verzonden
                            if (this._timer) clearTimeout(this._timer);
                            this._timer = setTimeout(() => {
                                document.getElementById('timeSetterForm').submit();
                            }, 500);
                        });

                        // Voor jaar input, luister naar Enter toets
                        if (datePicker.type === 'number') {
                            datePicker.addEventListener('keydown', function(e) {
                                if (e.key === 'Enter') {
                                    document.getElementById('timeSetterForm').submit();
                                }
                            });
                        }
                    }
                }
            }

            // Roep de initiële setup aan
            initDatePickerListener();

            // Status-message verwijderen na 5 seconden
            setTimeout(() => {
                const msg = document.getElementById('status-message');
                if (msg) {
                    msg.classList.remove('opacity-100');
                    msg.classList.add('opacity-0');

                    setTimeout(() => {
                        msg.remove();
                    }, 1000);
                }
            }, 5000);
        });
    </script>
    @stack('chart-scripts')
    @stack('trend-scripts')
    @stack('prediction-chart-scripts')
    @stack('scripts')
</x-app-layout>
