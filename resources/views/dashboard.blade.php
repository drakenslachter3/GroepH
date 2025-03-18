<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Energieverbruik Dashboard') }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="max-w-md mx-auto my-8 p-6 bg-white shadow-lg rounded-lg border border-gray-100">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Widget Configuratie</h2>

                <form action="{{ route('dashboard.setWidget') }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="space-y-2">
                        <label for="grid-position" class="block text-sm font-medium text-gray-700">Positie:</label>
                        <select name="grid_position" id="grid-position" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200">
                            @for ($i = 0; $i < count($gridLayout); $i++)
                                <option value="{{ $i }}">Positie {{ $i + 1 }}</option>
                                @endfor
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="widget-type" class="block text-sm font-medium text-gray-700">Widget Type:</label>
                        <select name="widget_type" id="widget-type" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200">
                            <option value="date-selector">Datum en Periode Selectie</option>
                            <option value="usage-prediction">Voorspelling en Prognose</option>
                            <option value="energy-status-electricity">Electra Status</option>
                            <option value="energy-status-gas">Gas Status</option>
                            <option value="historical-comparison">Historische Vergelijking</option>
                            <option value="energy-chart-electricity">Electra Grafiek</option>
                            <option value="energy-chart-gas">Gas Grafiek</option>
                            <option value="trend-analysis">Trend Analyse</option>
                            <option value="energy-suggestions">Energiebesparingstips</option>
                            <option value="budget-alert">Budget Waarschuwing</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2">
                        Widget Toevoegen
                    </button>
                </form>

                <div class="mt-6 border-t border-gray-200 pt-6 flex space-x-4">
                    <form action="{{ route('dashboard.resetLayout') }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full py-2 px-4 bg-red-500 hover:bg-red-600 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2">
                            Reset Layout
                        </button>
                    </form>

                    <button onclick="window.location.href='{{ route('budget.form') }}'" class="flex-1 py-2 px-4 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2">
                        Budget Aanpassen
                    </button>
                </div>
            </div>


            <div class="flex flex-wrap -mx-2">
                @foreach ($gridLayout as $item)
                @php
                $widgetSize = match($item) {
                'date-selector' => 'full',
                'usage-prediction' => 'medium',
                'energy-status-electricity', 'energy-status-gas' => 'small',
                'historical-comparison' => 'full',
                'energy-chart-electricity', 'energy-chart-gas' => 'large',
                'trend-analysis' => 'full',
                'energy-suggestions' => 'medium',
                default => 'full'
                };

                $widthClasses = match($widgetSize) {
                'small' => 'w-full sm:w-1/2 lg:w-1/4',
                'medium' => 'w-full sm:w-1/2 lg:w-1/3',
                'large' => 'w-full lg:w-1/2',
                'full' => 'w-full'
                };
                @endphp

                <div class="p-2 {{ $widthClasses }}">
                    <div class="h-full p-4 bg-white shadow-md rounded-lg">
                        @switch($item)
                        @case('date-selector')
                        <x-dashboard.date-selector :period="$period" :date="$date" :housingType="$housingType" />
                        @break

                        @case('usage-prediction')
                        <x-dashboard.usage-prediction
                            :electricityData="['kwh' => $totals['electricity_kwh'], 'percentage' => $totals['electricity_percentage']]"
                            :gasData="['m3' => $totals['gas_m3'], 'percentage' => $totals['gas_percentage']]"
                            :period="$period" />
                        @break

                        @case('energy-status-electricity')
                        <x-dashboard.energy-status
                            type="Elektriciteit"
                            :usage="$totals['electricity_kwh']"
                            :target="$totals['electricity_target']"
                            :cost="$totals['electricity_euro']"
                            :percentage="$totals['electricity_percentage']"
                            :status="$totals['electricity_status']"
                            unit="kWh" />
                        @break

                        @case('energy-status-gas')
                        <x-dashboard.energy-status
                            type="Gas"
                            :usage="$totals['gas_m3']"
                            :target="$totals['gas_target']"
                            :cost="$totals['gas_euro']"
                            :percentage="$totals['gas_percentage']"
                            :status="$totals['gas_status']"
                            unit="m³" />
                        @break

                        @case('historical-comparison')
                        <x-dashboard.historical-comparison
                            :weekData="['electricity' => 42.8, 'gas' => 12.3]"
                            :monthData="['electricity' => 180.5, 'gas' => 52.7]"
                            :yearComparisonData="['electricity' => 210.3, 'gas' => 57.1]" />
                        @break

                        @case('energy-chart-electricity')
                        <x-dashboard.energy-chart
                            type="electricity"
                            title="Elektriciteitsverbruik (kWh)"
                            buttonLabel="Toon Vorig Jaar"
                            buttonColor="blue"
                            :chartData="$chartData"
                            :period="$period" />
                        @break

                        @case('energy-chart-gas')
                        <x-dashboard.energy-chart
                            type="gas"
                            title="Gasverbruik (m³)"
                            buttonLabel="Toon Vorig Jaar"
                            buttonColor="yellow"
                            :chartData="$chartData"
                            :period="$period" />
                        @break

                        @case('trend-analysis')
                        <x-dashboard.trend-analysis
                            :electricityData="['thisYear' => [210, 195, 180], 'lastYear' => [230, 220, 200]]"
                            :gasData="['thisYear' => [120, 115, 90], 'lastYear' => [130, 125, 100]]" />
                        @break

                        @case('energy-suggestions')
                        <x-dashboard.energy-suggestions
                            :usagePattern="$usagePattern ?? 'avond'"
                            :housingType="$housingType" />
                        @break

                        @default
                        <p>Unknown widget type</p>
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
    </script>
    @stack('chart-scripts')
    @stack('scripts')
</x-app-layout>