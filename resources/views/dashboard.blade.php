<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Energieverbruik Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Datum en periode selectie component -->
            <x-date-selector :period="$period" :date="$date" :housingType="$housingType" />

            <!-- Voorspelling en prognose component -->
            <x-usage-prediction 
                :electricityData="['kwh' => $totals['electricity_kwh'], 'percentage' => $totals['electricity_percentage']]" 
                :gasData="['m3' => $totals['gas_m3'], 'percentage' => $totals['gas_percentage']]" 
                :period="$period" 
            />
<!-- Budgetstatus cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Elektriciteit Status Component -->
                <x-energy-status 
                    type="Elektriciteit" 
                    :usage="$totals['electricity_kwh']" 
                    :target="$totals['electricity_target']" 
                    :cost="$totals['electricity_euro']" 
                    :percentage="$totals['electricity_percentage']" 
                    :status="$totals['electricity_status']" 
                    unit="kWh" 
                />

                <!-- Gas Status Component -->
                <x-energy-status 
                    type="Gas" 
                    :usage="$totals['gas_m3']" 
                    :target="$totals['gas_target']" 
                    :cost="$totals['gas_euro']" 
                    :percentage="$totals['gas_percentage']" 
                    :status="$totals['gas_status']" 
                    unit="m³" 
                />
            </div>
            
            <!-- Historische vergelijking cards -->
            <x-historical-comparison 
                :weekData="['electricity' => 42.8, 'gas' => 12.3]"
                :monthData="['electricity' => 180.5, 'gas' => 52.7]"
                :yearComparisonData="['electricity' => 210.3, 'gas' => 57.1]"
            />

            <!-- Grafiek Elektriciteit -->
            <x-energy-chart 
                type="electricity" 
                title="Elektriciteitsverbruik (kWh)"
                buttonLabel="Toon Vorig Jaar"
                buttonColor="blue"
                :chartData="$chartData"
                :period="$period"
            />

            <!-- Grafiek Gas -->
            <x-energy-chart 
                type="gas" 
                title="Gasverbruik (m³)"
                buttonLabel="Toon Vorig Jaar"
                buttonColor="yellow"
                :chartData="$chartData"
                :period="$period"
            />

            <!-- Trend Analyse Component -->
            <x-trend-analysis 
                :electricityData="['thisYear' => [210, 195, 180, 170, 165, 168, 172, 175, 168, 182, 190, 200], 'lastYear' => [230, 220, 200, 185, 180, 182, 190, 195, 185, 200, 210, 225]]" 
                :gasData="['thisYear' => [120, 115, 90, 65, 40, 25, 20, 20, 35, 70, 100, 110], 'lastYear' => [130, 125, 100, 70, 45, 30, 25, 25, 40, 75, 110, 120]]" 
            />

            <!-- Gepersonaliseerde energiebesparingstips component -->
            <x-energy-suggestions 
                :usagePattern="$usagePattern ?? 'avond'" 
                :housingType="$housingType"
            />
        </div>
    </div>
    
    <!-- Budget waarschuwing component -->
    <x-budget-alert 
        :electricityPercentage="$totals['electricity_percentage']" 
        :gasPercentage="$totals['gas_percentage']" 
        threshold="80"
    />

    <!-- Charts.js scripts met verbeterde interactiviteit -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart data from PHP
        let chartData = {
            labels: [],
            electricity: {data: [], target: []},
            gas: {data: [], target: []},
            cost: {electricity: [], gas: []}
        };
        
        @if(isset($chartData))
            chartData = @json($chartData);
        @endif
        
        // Last year's data (mockup voor demo)
        const lastYearData = {
            electricity: chartData.electricity.data.map(value => value * (1 + (Math.random() * 0.3))),
            gas: chartData.gas.data.map(value => value * (1 + (Math.random() * 0.25)))
        };
        
        // Format voor labels op basis van periode
        const periodLabels = {
            'day': 'Uur',
            'month': 'Dag',
            'year': 'Maand'
        };
    </script>
    
    @stack('scripts')
    @stack('chart-scripts')
    @stack('trend-scripts')
    @stack('alert-scripts')
</x-app-layout>