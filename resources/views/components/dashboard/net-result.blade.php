@props(['date' => null,'period' => null,'energyConsumed' => null, 'energyProduced' => null])

@php
    $energyConsumed = array_filter($energyConsumed ?? [], 'is_numeric');
    $energyProduced = array_filter($energyProduced ?? [], 'is_numeric');

    $sumConsumed = array_sum($energyConsumed);
    $sumProduced = array_sum($energyProduced);

    $formattedEnergyConsumed = number_format($sumConsumed, 2);
    $formattedEnergyProduced = number_format($sumProduced, 2);

    $percentageProduced = $sumConsumed > 0
        ? number_format(($sumProduced / $sumConsumed) * 100, 2)
        : 0;

    $percentageConsumed = 100 - $percentageProduced;
@endphp

<div class="w-full p-2">
    <div class="flex flex-col">
        <div class="flex flex-row justify-between items-center w-full">
            <x-dashboard.widget-navigation :showPrevious="true" />
            <x-dashboard.widget-heading :title="'Netto resultante'" />
            <x-dashboard.widget-navigation :showNext="true" />
            <div class="tooltip relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 cursor-pointer hover:text-gray-600 dark:text-gray-300 dark:hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="tooltiptext invisible absolute z-10 px-3 py-2 text-sm bg-gray-800 text-white rounded shadow-lg -right-4 -bottom-20 w-48">
                    Voor u ziet u het netto resultante tussen geconsumeerde en geproduceerde energie.
                </span>
            </div>
        </div>
        <!-- Metrics -->
        <div class="space-y-2 mt-2">
            <!-- Target value -->
            <div class="flex justify-between items-center">
                <span class="text-gray-700 dark:text-gray-300">Verbruikte elektriciteit:</span>
                <span tabindex="0"
                    aria-label=""
                    class="font-bold dark:text-white">
                    {{ $formattedEnergyConsumed }} kWh
                </span>
            </div>
            <!-- Generated value -->
            <div class="flex justify-between items-center">
                <span class="text-gray-700 dark:text-gray-300">Opgewekte elektriciteit:</span>
                <span tabindex="0"
                    aria-label=""
                    class="font-bold dark:text-white">
                    {{ $formattedEnergyProduced }} kWh
                </span>
            </div>
            <!-- Generated value -->
            <div class="flex justify-between items-center">
                <span class="text-gray-700 dark:text-gray-300">U heeft <span class="font-bold">{{ $percentageProduced }}%</span> van uw stroomverbruik zelf opgewekt!</span>
                <span tabindex="0"
                    aria-label=""
                    class="font-bold dark:text-white">
                </span>
            </div><br>
            <!-- Legenda voor pie chart -->
            <div class="mt-5 flex space-x-4 text-sm">
                <!-- Zelf opgewekte elektriciteit -->
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-yellow-500 rounded-sm border border-yellow-600"></div>
                    <span class="text-gray-700 dark:text-gray-300">Zelf opgewekt</span>
                </div>
                <!-- Restant -->
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-gray-300 rounded-sm border border-gray-400"></div>
                    <span class="text-gray-700 dark:text-gray-300">Restant</span>
                </div>
            </div>
        </div>
    </div>
     <!-- Canvas for pie chart -->
    <div class="mt-6 w-56 flex justify-center">
        <canvas id="nettoPieChart" class="max-w-xs"></canvas>
    </div>
</div>

<!-- Load chartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const percentageConsumed = @json($percentageConsumed);
    const percentageProduced = @json($percentageProduced);

    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('nettoPieChart').getContext('2d');

        const data = {
            labels: ['Zelf opgewekte elektriciteit', 'Restant'],
            datasets: [{
                data: [percentageProduced, percentageConsumed],
                backgroundColor: ['#0079ff', '#E5E7EB'],
                borderWidth: 1
            }]
        };

        const options = {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            return `${label}: ${value}%`;
                        }
                    }
                }
            }
        };

        new Chart(ctx, {
            type: 'pie',
            data: data,
            options: options
        });
    });
</script>