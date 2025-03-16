<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Energieverbruik Dashboard') }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 p-4 bg-white shadow rounded">
                <label for="grid-position" class="block text-sm font-medium text-gray-700">Position:</label>
                <select id="grid-position" class="mt-1 p-2 border rounded w-full">
                    @for ($i = 0; $i < count($gridLayout); $i++)
                        <option value="{{ $i }}">Position {{ $i + 1 }}</option>
                    @endfor
                </select>

                <label for="widget-type" class="block mt-4 text-sm font-medium text-gray-700">Widget:</label>
                <select id="widget-type" class="mt-1 p-2 border rounded w-full">
                    <option value="date-selector">Datum en Periode Selectie</option>
                    <option value="usage-prediction">Voorspelling en Prognose</option>
                    <option value="energy-status">Energie Status</option>
                    <option value="historical-comparison">Historische Vergelijking</option>
                    <option value="energy-chart">Energie Grafiek</option>
                    <option value="trend-analysis">Trend Analyse</option>
                    <option value="energy-suggestions">Energiebesparingstips</option>
                    <option value="budget-alert">Budget Waarschuwing</option>
                </select>

                <button onclick="setWidget()" class="mt-4 px-4 py-2 bg-green-500 text-white rounded">Set Widget</button>
                <button onclick="saveLayout()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded">Save Layout</button>
                <button onclick="resetLayout()" class="mt-4 px-4 py-2 bg-red-500 text-white rounded">Reset Layout</button>
                <button onclick="window.location.href='{{ route('budget.form') }}'" class="mt-4 px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 transition duration-200">Budget aanpassen</button>
                
                
            </div>
            <div class="mb-4 p-4 bg-gray-100 rounded flex justify-between items-center">
                <p class="text-sm text-gray-700">Laatste update: <span id="last-updated">{{ $lastUpdated ?? 'Niet beschikbaar' }}</span></p>
                <p class="text-sm text-gray-700">Volgende update: <span id="next-update">{{ $refreshTime ?? 'Niet beschikbaar' }}</span></p>
                <button onclick="window.location.reload()" class="mt-4 px-4 py-2 bg-gray-500 text-white rounded">Verversen</button>
            </div>
            <div class="flex flex-wrap -mx-2">
                @foreach ($gridLayout as $item)
                    @php
                        $widgetSize = match($item) {
                            'date-selector' => 'small',
                            'usage-prediction' => 'medium',
                            'energy-status-electricity', 'energy-status-gas' => 'small',
                            'historical-comparison' => 'medium',
                            'energy-chart-electricity', 'energy-chart-gas' => 'large',
                            'trend-analysis' => 'large',
                            'energy-suggestions' => 'medium',
                            default => 'medium'
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

    <script>
        function setWidget() {
            const position = document.getElementById('grid-position').value;
            const widgetType = document.getElementById('widget-type').value;
            
            // Send AJAX request to update widget
            fetch('/dashboard/update-widget', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    position: position,
                    widgetType: widgetType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        }

        function saveLayout() {
            fetch('/dashboard/save-layout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Layout saved successfully!');
                }
            });
        }

        function resetLayout() {
            if (confirm('Are you sure you want to reset the layout to default?')) {
                fetch('/dashboard/reset-layout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    }
                });
            }
        }

        setTimeout(function() {
            window.location.reload();
        }, 65*1000);
    </script>
</x-app-layout>