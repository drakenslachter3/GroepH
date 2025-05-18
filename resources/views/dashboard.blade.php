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

            <!-- Display smart meters for the user -->
            {{-- @if (Auth::check())
                @include('components.user-meter-readings', ['user' => Auth::user()])
            @endif --}}

            <div class="bg-white shadow-lg rounded-lg mb-8 dark:bg-gray-800">
                <!-- Toggle button for the entire config section -->
                <div class="p-4">
                    <!-- Dropdown button, last update info & refresh button in one row -->
                    <div class="flex justify-between items-center">
                        <!-- Dropdown button with title and icon -->
                        <button id="toggleConfigSection"
                            class="flex items-center text-left focus:outline-none dark:text-white">
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
                            <button onclick="window.location.reload()"
                                class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded transition duration-200 text-sm">
                                Verversen
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Content section (collapsible) -->
                <div id="configSectionContent" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 border-t">
                        <!-- Widget Configuration Section -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-6 dark:text-white">Widget Configuratie
                            </h2>
                            <form action="{{ route('dashboard.setWidget') }}" method="POST" class="space-y-6">
                                @csrf
                                <div class="space-y-2">
                                    <label for="grid-position"
                                        class="block text-sm font-medium text-gray-700 dark:text-white">Positie:</label>
                                    <select name="grid_position" id="grid-position"
                                        class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200">
                                        @for ($i = 0; $i < count($gridLayout); $i++)
                                            <option value="{{ $i }}">Positie {{ $i + 1 }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label for="widget-type"
                                        class="block text-sm font-medium text-gray-700 dark:text-white">Widget
                                        Type:</label>
                                    <select name="widget_type" id="widget-type"
                                        class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200">
                                        <option value="usage-prediction">Voorspelling en Prognose</option>
                                        <option value="energy-status-electricity">Electra Status</option>
                                        <option value="energy-status-gas">Gas Status</option>
                                        <option value="historical-comparison">Historische Vergelijking</option>
                                        <option value="energy-chart-electricity">Electra Grafiek</option>
                                        <option value="energy-chart-gas">Gas Grafiek</option>
                                        <option value="trend-analysis">Trend Analyse</option>
                                        <option value="energy-suggestions">Energiebesparingstips</option>
                                        <option value="energy-prediction-chart-electricity">Elektriciteit Voorspelling</option>
                                        <option value="energy-prediction-chart-gas">Gas Voorspelling</option>
                                        <option value="budget-alert">Budget Waarschuwing</option>
                                        <option value="switch-meter">Selecteer meter</option>
                                    </select>
                                </div>

                                <button type="submit"
                                    class="w-full py-3 px-4 bg-green-500 hover:bg-green-600 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2">
                                    Widget Toevoegen
                                </button>
                            </form>

                            <div class="mt-6 border-t border-gray-200 pt-6 flex space-x-4">
                                <form action="{{ route('dashboard.resetLayout') }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                        class="w-full py-2 px-4 bg-red-500 hover:bg-red-600 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2">
                                        Reset Layout
                                    </button>
                                </form>

                                <button onclick="window.location.href='{{ route('budget.form') }}'"
                                    class="flex-1 py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2">
                                    Budget Aanpassen
                                </button>
                            </div>
                        </div>

                        <!-- Date Selector Section -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-6">Datum en Periode</h2>

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
                                    <div class="flex space-x-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="period" value="day" {{ $period === 'day' ? 'checked' : '' }} class="hidden">
                                            <span class="px-4 py-2 rounded-md cursor-pointer {{ $period === 'day' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                Dag
                                            </span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="period" value="month" {{ $period === 'month' ? 'checked' : '' }} class="hidden">
                                            <span class="px-4 py-2 rounded-md cursor-pointer {{ $period === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                Maand
                                            </span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="period" value="year" {{ $period === 'year' ? 'checked' : '' }} class="hidden">
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

            <div class="flex flex-wrap -mx-2">
                @foreach ($gridLayout as $item)
                    @php
                        // Skip the date-selector since we've integrated it into the top section
                        if ($item === 'date-selector') {
                            continue;
                        }

                        $widgetSize = match ($item) {
                            'usage-prediction' => 'large',
                            'energy-status-electricity', 'energy-status-gas' => 'small',
                            'historical-comparison' => 'full',
                            'energy-chart-electricity', 'energy-chart-gas' => 'large',
                            'trend-analysis' => 'full',
                            'energy-suggestions' => 'large',
                            'energy-prediction-chart-electricity' => 'full',
                            'energy-prediction-chart-gas' => 'full',
                            'switch-meter' => 'large',
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
                                @case('usage-prediction')
                                    <x-dashboard.usage-prediction :electricityData="[
                                        'kwh' => $totals['electricity_kwh'],
                                        'percentage' => $totals['electricity_percentage'],
                                    ]" :gasData="['m3' => $totals['gas_m3'], 'percentage' => $totals['gas_percentage']]" :period="$period" />
                                @break

                                @case('energy-status-electricity')
                                    <x-dashboard.energy-status type="Elektriciteit" :usage="$liveData['electricity']['usage'] ?? 0" :target="$liveData['electricity']['target'] ?? 0"
                                        :cost="$liveData['electricity']['cost'] ?? 0" :percentage="$liveData['electricity']['percentage'] ?? 0" :status="$liveData['electricity']['status'] ?? 'goed'" :date="$date"
                                        :period="$period" :liveData="$liveData['electricity'] ?? null" unit="kWh" />
                                @break

                                @case('energy-status-gas')
                                    <x-dashboard.energy-status type="Gas" :usage="$liveData['gas']['usage'] ?? 0" :target="$liveData['gas']['target'] ?? 0"
                                        :cost="$liveData['gas']['cost'] ?? 0" :percentage="$liveData['gas']['percentage'] ?? 0" :status="$liveData['gas']['status'] ?? 'goed'" :date="$date"
                                        :period="$period" :liveData="$liveData['gas'] ?? null" unit="m³" />
                                @break

                                @case('historical-comparison')
                                    <x-dashboard.historical-comparison :weekData="['electricity' => 42.8, 'gas' => 12.3]" :monthData="['electricity' => 180.5, 'gas' => 52.7]"
                                        :yearComparisonData="['electricity' => 210.3, 'gas' => 57.1]" />
                                @break

                                @case('energy-chart-electricity')
                                    <x-dashboard.energy-chart type="electricity" title="Elektriciteitsverbruik (kWh)"
                                        buttonLabel="Toon Vorig Jaar" buttonColor="blue" :chartData="$meterDataForPeriod['current_data'] ?? []" :period="$period"
                                        :date="$date" />
                                @break

                                @case('energy-chart-gas')
                                    <x-dashboard.energy-chart type="gas" title="Gasverbruik (m³)"
                                        buttonLabel="Toon Vorig Jaar" buttonColor="yellow" :chartData="$meterDataForPeriod['current_data'] ?? []"
                                        :period="$period" :date="$date" />
                                @break

                                @case('energy-suggestions')
                                    <x-dashboard.energy-suggestions :usagePattern="$usagePattern ?? 'avond'" :housingType="$housingType" :season="date('n') >= 3 && date('n') <= 5
                                        ? 'lente'
                                        : (date('n') >= 6 && date('n') <= 8
                                            ? 'zomer'
                                            : (date('n') >= 9 && date('n') <= 11
                                                ? 'herfst'
                                                : 'winter'))" />
                                @break

                                @case('energy-prediction-chart-electricity')
                                    <x-dashboard.energy-prediction-chart :currentData="$predictionData['electricity'] ?? []" :budgetData="$budgetData['electricity'] ?? []"
                                        type="electricity" :period="$period" :percentage="$predictionPercentage['electricity'] ?? 0" :confidence="$predictionConfidence['electricity'] ?? 75"
                                        :yearlyConsumptionToDate="$yearlyConsumptionToDate['electricity'] ?? 0" :dailyAverageConsumption="$dailyAverageConsumption['electricity'] ?? 0" />
                                @break

                                @case('energy-prediction-chart-gas')
                                    <x-dashboard.energy-prediction-chart :currentData="$predictionData['gas'] ?? []" :budgetData="$budgetData['gas'] ?? []" 
                                        type="gas" :period="$period" :percentage="$predictionPercentage['gas'] ?? 0" :confidence="$predictionConfidence['gas'] ?? 75" 
                                        :yearlyConsumptionToDate="$yearlyConsumptionToDate['gas'] ?? 0" :dailyAverageConsumption="$dailyAverageConsumption['gas'] ?? 0" />
                                @break

                                @case('switch-meter')
                                    <x-dashboard.switch-meter :meters="\App\Models\SmartMeter::getAllSmartMetersForCurrentUser()" :selectedMeterId="\App\Models\UserGridLayout::getSelectedSmartMeterForCurrentUser()" />
                                @break

                                @default
                            @endswitch
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Add this at the bottom before closing the layout -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
