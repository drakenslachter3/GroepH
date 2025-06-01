@props([
    'title',
    'currentData',
    'budgetData',
    'type',
    'period',
    'percentage',
    'confidence',
    'yearlyConsumptionToDate' => 0,
    'dailyAverageConsumption' => 0,
    'date' => null,
    'currentMonthName' => null,
    'monthlyBudgetValue' => null,
])

{{-- Set default values for any missing props --}}
@php
    // Calculate yearlyBudgetTarget if not provided
    $yearlyBudgetTarget = $yearlyBudgetTarget ?? ($budgetData['target'] ?? 0);

    // Calculate if prediction exceeds budget if not provided
    $predictedTotal = $currentData['expected'] ?? 0;
    $exceedingPercentage = $exceedingPercentage ?? round(abs(($predictedTotal / $yearlyBudgetTarget) * 100 - 100), 1);
    $isExceedingBudget = $isExceedingBudget ?? $predictedTotal > $yearlyBudgetTarget;
@endphp

<section aria-labelledby="prediction-chart-title"
    class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
    <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
        {{-- Navigation and Heading Section --}}
        <div class="flex justify-between items-center mb-4">
            <x-dashboard.widget-navigation :showPrevious="true" />
            <div class="text-center flex-1">
                <h3 id="prediction-chart-title" class="text-lg font-semibold dark:text-white">
                    {{ $type === 'electricity' ? 'Elektriciteit' : 'Gas' }} Voorspelling
                    <span
                        class="text-sm bg-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-100 text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-800 px-2 py-1 rounded ml-2 dark:bg-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-900/30 dark:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-300">
                        {{ ucfirst($period) }}
                    </span>

                    @if (isset($currentMonthName) && $period === 'month')
                        <span
                            class="text-sm bg-gray-100 text-gray-800 px-2 py-1 rounded ml-2 dark:bg-gray-700 dark:text-gray-200">
                            {{ $currentMonthName }}
                        </span>
                    @endif
                </h3>
                @if (isset($date))
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $date }}</p>
                @endif
            </div>
            <x-dashboard.widget-navigation :showNext="true" />
        </div>

        {{-- Confidence and Budget Status Section --}}
        <div class="mb-4">
            <div class="flex justify-between items-center mb-3">
                <div class="flex items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Betrouwbaarheid: </span>
                    <div class="w-24 h-4 bg-gray-200 rounded-full ml-2 dark:bg-gray-700">
                        <div class="h-4 rounded-full {{ $confidence > 80 ? 'bg-green-500' : ($confidence > 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                            style="width: {{ $confidence }}%"></div>
                    </div>
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">{{ $confidence }}%</span>
                </div>

                <div
                    class="text-sm text-{{ $percentage <= 100 ? 'green' : 'red' }}-600 dark:text-{{ $percentage <= 100 ? 'green' : 'red' }}-400 font-medium">
                    @if ($period == 'year')
                        Verbruik tot nu toe: {{ number_format($percentage, 1) }}%
                    @else
                        {{ $percentage > 100 ? 'Overschrijding' : 'Binnen budget' }}:
                        {{ number_format(abs($percentage - 100), 1) }}%
                    @endif
                </div>
            </div>

            {{-- Prediction Chart Canvas --}}
            <div class="relative" style="height: 350px;">
                <canvas id="predictionChart{{ $type }}{{ $period }}"></canvas>
            </div>

            {{-- Budget Information --}}
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 flex justify-between">
                @if ($period == 'day')
                    {{-- Day view: Show daily budget using the values from budgetData --}}
                    <span>Dagelijks budget: {{ number_format(($budgetData['monthly_target'] * 12) / 365, 1) }}
                        {{ $type === 'electricity' ? 'kWh' : 'm³' }}/dag</span>
                    <span>Uurlijks budget: {{ number_format(($budgetData['monthly_target'] * 12) / 365 / 24, 2) }}
                        {{ $type === 'electricity' ? 'kWh' : 'm³' }}/uur</span>
                @elseif($period == 'month' && isset($monthlyBudgetValue))
                    {{-- Month view: Show monthly budget --}}
                    <span>Maandbudget: {{ number_format($monthlyBudgetValue, 0) }}
                        {{ $type === 'electricity' ? 'kWh' : 'm³' }}</span>
                    <span>Dagelijks budget: {{ number_format($monthlyBudgetValue / 30, 1) }}
                        {{ $type === 'electricity' ? 'kWh' : 'm³' }}/dag</span>
                @else
                    {{-- Year view: Show yearly budget --}}
                    <span>Jaarbudget: {{ number_format($yearlyBudgetTarget, 0) }}
                        {{ $type === 'electricity' ? 'kWh' : 'm³' }}</span>
                    <span>Maandbudget: {{ number_format($yearlyBudgetTarget / 12, 0) }}
                        {{ $type === 'electricity' ? 'kWh' : 'm³' }}/maand</span>
                @endif
            </div>
        </div>

        {{-- Energy Consumption Details Section --}}
        <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2">
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400">Verbruik dit jaar:</span>
                <p class="text-sm font-medium text-gray-800 dark:text-white" data-id="totalThisYear">
                    {{ number_format($yearlyConsumptionToDate, 1) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}
                </p>
            </div>
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400">Gemiddeld per dag:</span>
                <p class="text-sm font-medium text-gray-800 dark:text-white" data-id="dailyAverage">
                    {{ number_format($dailyAverageConsumption, 1) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}/dag
                </p>
            </div>
        </div>

        {{-- Predictions Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            {{-- Expected Scenario Card --}}
            <div class="bg-gray-50 p-4 rounded-lg dark:bg-gray-700">
                <h4 class="font-medium text-gray-700 mb-2 dark:text-white">Verwachte uitkomst</h4>
                <p
                    class="text-2xl font-bold text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-600 dark:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-400">
                    {{ number_format($currentData['expected'], 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    @if ($isExceedingBudget)
                        Overschrijding budget: {{ number_format($exceedingPercentage, 1) }}%
                    @else
                        Onder budget: {{ number_format($exceedingPercentage, 1) }}%
                    @endif
                </p>
            </div>

            {{-- Best Case Card --}}
            <div class="bg-green-50 p-4 rounded-lg dark:bg-green-900/30">
                <h4 class="font-medium text-green-700 mb-2 dark:text-green-400">Best case scenario</h4>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($currentData['best_case'], 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $currentData['best_case'] > $yearlyBudgetTarget ? 'Overschrijding' : 'Onder' }} budget:
                    {{ number_format(abs(($currentData['best_case'] / $yearlyBudgetTarget) * 100 - 100), 1) }}%
                </p>
            </div>

            {{-- Worst Case Card --}}
            <div class="bg-red-50 p-4 rounded-lg dark:bg-red-900/30">
                <h4 class="font-medium text-red-700 mb-2 dark:text-red-400">Worst case scenario</h4>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ number_format($currentData['worst_case'], 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $currentData['worst_case'] > $yearlyBudgetTarget ? 'Overschrijding' : 'Onder' }} budget:
                    {{ number_format(abs(($currentData['worst_case'] / $yearlyBudgetTarget) * 100 - 100), 1) }}%
                </p>
            </div>
        </div>

        {{-- Recommendations Based on Prediction --}}
        <div
            class="mt-6 p-4 bg-{{ $isExceedingBudget ? 'red' : 'green' }}-50 rounded-lg dark:bg-{{ $isExceedingBudget ? 'red' : 'green' }}-900/30">
            <h4
                class="font-medium text-{{ $isExceedingBudget ? 'red' : 'green' }}-700 dark:text-{{ $isExceedingBudget ? 'red' : 'green' }}-400 mb-2">
                {{ $isExceedingBudget ? 'Actie nodig' : 'Goed op weg' }}
            </h4>
            <p class="text-gray-700 dark:text-gray-300">
                @if ($exceedingPercentage > 30 && $isExceedingBudget)
                    U zit momenteel significant boven uw jaarbudget. Overweeg maatregelen om uw
                    {{ $type === 'electricity' ? 'elektriciteits' : 'gas' }}verbruik te verminderen.
                @elseif($isExceedingBudget)
                    U zit momenteel iets boven uw jaarbudget. Let op uw verbruik om binnen het budget te blijven.
                @elseif($exceedingPercentage < 10)
                    U zit goed op schema om binnen uw jaarbudget te blijven. Blijf uw verbruik in de gaten houden.
                @else
                    U zit goed op schema en gebruikt minder dan verwacht. Ga zo door!
                @endif
            </p>
        </div>
    </div>
</section>

@push('prediction-chart-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded!');
                return;
            }

            // Check if dark mode is active
            const isDarkMode = document.documentElement.classList.contains('dark') ||
                document.querySelector('html').classList.contains('dark') ||
                window.matchMedia('(prefers-color-scheme: dark)').matches;

            // Set the text color based on dark mode
            const textColor = isDarkMode ? '#FFFFFF' : '#000000';
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

            // Parse data from PHP props
            const currentData = @json($currentData);
            const budgetData = @json($budgetData);
            const energyType = @json($type);
            const periodType = @json($period);

            console.log("Chart setup for", energyType, periodType);
            console.log("Current data keys:", Object.keys(currentData));

            // Define colors for different energy types
            const mainColor = energyType === 'electricity' ?
                'rgb(59, 130, 246)' : 'rgb(245, 158, 11)';
            const mainColorLight = energyType === 'electricity' ?
                'rgba(59, 130, 246, 0.1)' : 'rgba(245, 158, 11, 0.1)';

            // Define x-axis labels based on period
            const labels = getLabels(periodType);

            // Prepare the chart data
            const ctx = document.getElementById(`predictionChart${energyType}${periodType}`);
            if (!ctx) {
                console.error(`Chart canvas not found: predictionChart${energyType}${periodType}`);
                return;
            }

            // Calculate y-axis min/max based on the period type for proper scaling
            const yAxisConfig = getYAxisConfig(periodType, energyType, currentData, budgetData);

            // Create datasets
            const chartData = {
                labels: labels,
                datasets: []
            };

            // Only add datasets if they exist in the data
            // Actual data
            if (Array.isArray(currentData.actual)) {
                chartData.datasets.push({
                    label: 'Werkelijk verbruik',
                    data: currentData.actual,
                    borderColor: mainColor,
                    backgroundColor: mainColorLight,
                    tension: 0.2,
                    fill: false,
                    pointRadius: 4,
                    pointBackgroundColor: mainColor,
                    borderWidth: 3,
                    order: 0 // Put actual data at the foreground
                });
            }

            // Budget line
            const budgetLine = getPeriodBudgetLine(periodType, budgetData);
            if (Array.isArray(budgetLine)) {
                chartData.datasets.push({
                    label: 'Budget',
                    data: budgetLine,
                    borderColor: isDarkMode ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)',
                    backgroundColor: 'rgba(0, 0, 0, 0)',
                    borderWidth: 2.5,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0,
                    pointRadius: 0,
                    order: 1 // Place budget line behind other elements
                });
            }

            // Best case scenario
            if (Array.isArray(currentData.best_case_line)) {
                chartData.datasets.push({
                    label: 'Best case',
                    data: currentData.best_case_line,
                    borderColor: 'rgba(16, 185, 129, 0.6)',
                    borderDash: [5, 5],
                    tension: 0.3,
                    fill: false,
                    pointRadius: 0,
                    borderWidth: 2,
                    order: 2
                });
            }

            // Create prediction band (area between best and worst case)
            if (Array.isArray(currentData.worst_case_line) &&
                Array.isArray(currentData.best_case_line) &&
                currentData.worst_case_line.length > 0 &&
                currentData.best_case_line.length > 0) {

                // Add prediction area
                chartData.datasets.push({
                    type: 'line',
                    label: 'Voorspellingsmarge',
                    data: currentData.worst_case_line,
                    backgroundColor: 'rgba(156, 163, 175, 0.2)', // Gray with transparency
                    borderWidth: 0,
                    tension: 0.3,
                    fill: '-1', // Fill to previous dataset (best case)
                    pointRadius: 0,
                    order: 3
                });

                // Worst case scenario
                chartData.datasets.push({
                    label: 'Worst case',
                    data: currentData.worst_case_line,
                    borderColor: 'rgba(239, 68, 68, 0.6)',
                    borderDash: [5, 5],
                    tension: 0.3,
                    fill: false,
                    pointRadius: 0,
                    borderWidth: 2,
                    order: 4
                });
            }

            // Expected prediction
            if (Array.isArray(currentData.prediction)) {
                chartData.datasets.push({
                    label: 'Verwachte trend',
                    data: currentData.prediction,
                    borderColor: 'rgba(107, 114, 128, 0.8)',
                    borderDash: [5, 5],
                    backgroundColor: 'rgba(107, 114, 128, 0.1)',
                    tension: 0.3,
                    fill: false,
                    pointRadius: 0,
                    borderWidth: 2,
                    order: 5
                });
            }

            // Create the chart
            const predictionChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                footer: function(tooltipItems) {
                                    if (tooltipItems.length === 0) return '';

                                    const datasetIndex = tooltipItems[0].datasetIndex;
                                    const dataset = chartData.datasets[datasetIndex];

                                    if (dataset.label === 'Werkelijk verbruik') {
                                        const index = tooltipItems[0].dataIndex;
                                        if (index >= 0 && index < budgetLine.length) {
                                            const budgetValue = budgetLine[index] || 0;
                                            const actualValue = dataset.data[index] || 0;
                                            if (budgetValue > 0) {
                                                const percentage = Math.round((actualValue /
                                                    budgetValue) * 100);
                                                return `Budget: ${budgetValue.toFixed(2)} ${energyType === 'electricity' ? 'kWh' : 'm³'} (${percentage}%)`;
                                            }
                                        }
                                    }
                                    return '';
                                }
                            }
                        },
                        legend: {
                            labels: {
                                color: textColor
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: getPeriodLabel(periodType),
                                color: textColor
                            },
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: energyType === 'electricity' ? 'Elektriciteit (kWh)' : 'Gas (m³)',
                                color: textColor
                            },
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                callback: function(value) {
                                    // Format ticks to prevent decimal points for larger values
                                    return Number.isInteger(value) ? value : value.toFixed(1);
                                }
                            },
                            beginAtZero: true,
                            min: yAxisConfig.min,
                            max: yAxisConfig.max,
                            suggestedMax: yAxisConfig.suggestedMax
                        }
                    }
                }
            });

            // Helper function to get budget line appropriate for the period
            function getPeriodBudgetLine(period, budgetData) {
                const length = getLabels(period).length;

                // Use real budget values - should be straight lines
                switch (period) {
                    case 'day':
                        // Daily budget per hour (straight line)
                        const dailyTarget = budgetData.monthly_target / new Date(new Date().getFullYear(),
                            new Date().getMonth() + 1, 0).getDate();
                        const hourlyBudget = dailyTarget / 24;
                        return Array(length).fill(hourlyBudget);

                    case 'month':
                        // Daily budget (straight line)
                        const monthlyTarget = budgetData.monthly_target;
                        const daysInMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0)
                            .getDate();
                        const dailyBudget = monthlyTarget / daysInMonth;
                        return Array(length).fill(dailyBudget);

                    case 'year':
                        // Use actual monthly budgets if available, otherwise equal distribution
                        if (budgetData.line && Array.isArray(budgetData.line) && budgetData.line.length === 12) {
                            return budgetData.line;
                        }
                        // Fallback to equal monthly distribution
                        const monthlyBudgetValue = budgetData.target / 12;
                        return Array(12).fill(monthlyBudgetValue);

                    default:
                        return Array(length).fill(0);
                }
            }

            // Helper function to get y-axis configuration based on period
            function getYAxisConfig(period, energyType, currentData, budgetData) {
                // Find the maximum value in all data arrays
                let maxValues = [];

                // Check actual data
                if (currentData.actual && Array.isArray(currentData.actual)) {
                    maxValues.push(...currentData.actual.filter(val => val !== null && val !== undefined && val >
                        0));
                }

                // Check prediction data
                if (currentData.prediction && Array.isArray(currentData.prediction)) {
                    maxValues.push(...currentData.prediction.filter(val => val !== null && val !== undefined &&
                        val > 0));
                }

                // Check best/worst case data
                if (currentData.best_case_line && Array.isArray(currentData.best_case_line)) {
                    maxValues.push(...currentData.best_case_line.filter(val => val !== null && val !== undefined &&
                        val > 0));
                }

                if (currentData.worst_case_line && Array.isArray(currentData.worst_case_line)) {
                    maxValues.push(...currentData.worst_case_line.filter(val => val !== null && val !== undefined &&
                        val > 0));
                }

                // Check budget line
                const budgetLine = getPeriodBudgetLine(period, budgetData);
                if (Array.isArray(budgetLine)) {
                    maxValues.push(...budgetLine.filter(val => val !== null && val !== undefined && val > 0));
                }

                // Find the actual maximum value
                const dataMax = maxValues.length > 0 ? Math.max(...maxValues) : 1;

                // Add 20% padding above the highest value for better visualization
                const maxWithPadding = dataMax * 1.2;

                // Ensure minimum scale for very small values
                const minScale = energyType === 'electricity' ? 0.5 : 0.2;
                const finalMax = Math.max(maxWithPadding, minScale);

                return {
                    min: 0,
                    max: finalMax,
                    suggestedMax: finalMax
                };
            }

            // Helper functions for chart labels
            function getLabels(period) {
                switch (period) {
                    case 'day':
                        return Array.from({
                            length: 24
                        }, (_, i) => `${i}:00`);
                    case 'month':
                        const daysInMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0)
                            .getDate();
                        return Array.from({
                            length: daysInMonth
                        }, (_, i) => `${i + 1}`);
                    case 'year':
                    default:
                        return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                }
            }

            function getPeriodLabel(period) {
                switch (period) {
                    case 'day':
                        return 'Uren';
                    case 'month':
                        return 'Dagen';
                    case 'year':
                    default:
                        return 'Maanden';
                }
            }

            // Watch for theme changes
            const themeWatcher = () => {
                // Use matchMedia for system preference changes
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateChartColors);

                // Use MutationObserver for class changes on HTML element
                const darkModeObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'class') {
                            updateChartColors();
                        }
                    });
                });

                darkModeObserver.observe(document.documentElement, {
                    attributes: true
                });
            };

            function updateChartColors() {
                const isDarkNow = document.documentElement.classList.contains('dark') ||
                    document.querySelector('html').classList.contains('dark') ||
                    window.matchMedia('(prefers-color-scheme: dark)').matches;

                const newTextColor = isDarkNow ? '#FFFFFF' : '#000000';
                const newGridColor = isDarkNow ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

                // Update budget line color for dark/light mode
                // Find the budget dataset
                const budgetDatasetIndex = chartData.datasets.findIndex(ds => ds.label === 'Budget');
                if (budgetDatasetIndex !== -1) {
                    predictionChart.data.datasets[budgetDatasetIndex].borderColor =
                        isDarkNow ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';
                }

                // Update scales colors
                predictionChart.options.scales.x.title.color = newTextColor;
                predictionChart.options.scales.y.title.color = newTextColor;
                predictionChart.options.scales.x.ticks.color = newTextColor;
                predictionChart.options.scales.y.ticks.color = newTextColor;
                predictionChart.options.scales.x.grid.color = newGridColor;
                predictionChart.options.scales.y.grid.color = newGridColor;

                // Update legend text color
                predictionChart.options.plugins.legend.labels.color = newTextColor;

                // Update the chart
                predictionChart.update();
            }

            // Update energy statistics display with real data
            function updateEnergyStatistics() {
                // Use actual values from PHP props instead of generating random values
                const yearToDateValue = @json($yearlyConsumptionToDate);
                const dailyAverageValue = @json($dailyAverageConsumption);
                const unit = energyType === 'electricity' ? 'kWh' : 'm³';

                // Find and update the elements
                const yearToDateElement = document.querySelector('[data-id="totalThisYear"]');
                const dailyAverageElement = document.querySelector('[data-id="dailyAverage"]');

                if (yearToDateElement) {
                    yearToDateElement.textContent = `${Number(yearToDateValue).toFixed(1)} ${unit}`;
                }

                if (dailyAverageElement) {
                    dailyAverageElement.textContent = `${Number(dailyAverageValue).toFixed(1)} ${unit}/dag`;
                }
            }

            // Call updateEnergyStatistics after chart is loaded
            setTimeout(updateEnergyStatistics, 500);

            // Initialize theme watcher
            themeWatcher();
        });
    </script>
@endpush
