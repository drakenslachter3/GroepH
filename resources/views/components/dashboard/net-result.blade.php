@props(['date' => null,'period' => null,'energyConsumed' => null, 'energyProduced' => null])

@php
    $energyConsumed = array_filter($energyConsumed ?? [], 'is_numeric');
    $energyProduced = array_filter($energyProduced ?? [], 'is_numeric');

    // Hardcoded voor test
    $sumConsumed = 2.00;
    $sumProduced = 3.00;

    $formattedEnergyConsumed = number_format($sumConsumed, 2);
    $formattedEnergyProduced = number_format($sumProduced, 2);

    $hasSurplus = $sumProduced > $sumConsumed;
    $surplusEnergy = $hasSurplus ? $sumProduced - $sumConsumed : 0;
    $formattedSurplus = number_format($surplusEnergy, 2);

    $rawPercentageProduced = $sumConsumed > 0
        ? ($sumProduced / $sumConsumed) * 100
        : 0;

    $percentageProduced = number_format(min(100, $rawPercentageProduced), 2);
    $percentageConsumed = number_format(100 - $percentageProduced, 2);
    $percentageSurplus = number_format(max(0, $rawPercentageProduced - 100), 2);
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
            <!-- Verbruikte elektriciteit -->
            <div class="flex justify-between items-center">
                <span class="text-gray-700 dark:text-gray-300">Verbruikte elektriciteit:</span>
                <span class="font-bold dark:text-white">{{ $formattedEnergyConsumed }} kWh</span>
            </div>

            <!-- Opgewekte elektriciteit -->
            <div class="flex justify-between items-center">
                <span class="text-gray-700 dark:text-gray-300">Opgewekte elektriciteit:</span>
                <span class="font-bold dark:text-white">{{ $formattedEnergyProduced }} kWh</span>
            </div>

            
            <!-- Overschot -->
            @if ($hasSurplus)
            <div class="flex justify-between items-center">
                <span class="text-gray-700 dark:text-gray-300">Overschot opgewekte energie:</span>
                <span class="font-bold dark:text-white">{{ $formattedSurplus }} kWh</span>
            </div>
            @endif

            <!-- Percentage zelf opgewekt -->
            <div class="flex flex-col justify-between items-start gap-1 py-2">
                @if($percentageProduced > 0)
                <span class="text-gray-700 dark:text-gray-300">
                    U heeft <span class="font-bold">{{ $percentageProduced }}%</span> van uw stroomverbruik zelf opgewekt!
                </span>
                @endif

                @if($percentageSurplus > 0)
                    <span class="text-gray-700 dark:text-gray-300">
                        U heeft <span class="font-bold">{{ $percentageSurplus }}%</span> meer opgewekt dan u heeft verbruikt!
                    </span>
                @endif
            </div>

            <!-- Legenda -->
            <div class="mt-5 flex space-x-4 text-sm justify-center">
                <!-- Zelf opgewekt -->
                @if($percentageProduced > 0)
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 rounded-sm border" style="background-color: #0079ff; border-color: #0079ff;"></div>
                    <span class="text-gray-700 dark:text-gray-300">Zelf opgewekt</span>
                </div>
                @endif

                <!-- Restant -->
                @if($percentageConsumed > 0)
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-gray-300 rounded-sm border border-gray-400"></div>
                    <span class="text-gray-700 dark:text-gray-300">Restant</span>
                </div>
                @endif
                <!-- Overschot (optioneel) -->
                @if($percentageSurplus > 0)
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-blue-200 rounded-sm border border-blue-400"></div>
                    <span class="text-gray-700 dark:text-gray-300">Overschot</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Canvas voor pie chart -->
    <div class="mt-6 w-full flex justify-center">
        <div class="w-56 flex justify-center">
            <canvas id="nettoPieChart" class="max-w-xs"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js laden -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const percentageConsumed = @json($percentageConsumed);
const percentageProduced = @json($percentageProduced);
const percentageSurplus = @json($percentageSurplus);

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('nettoPieChart').getContext('2d');

    const data = {
        labels: ['Zelf opgewekt', 'Restant', 'Overschot'],
        datasets: [{
            data: [percentageProduced, percentageConsumed, percentageSurplus],
            backgroundColor: ['#0079ff', '#E5E7EB', '#93c5fd'], // donkerblauw, grijs, lichtblauw
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
