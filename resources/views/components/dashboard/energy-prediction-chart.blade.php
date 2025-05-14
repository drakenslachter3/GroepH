@props(['currentData', 'budgetData', 'type', 'period', 'percentage', 'confidence'])

{{-- Set default values for any missing props --}}
@php
// Calculate yearlyBudgetTarget if not provided
$yearlyBudgetTarget = $yearlyBudgetTarget ?? $budgetData['target'] ?? 0;

// Calculate if prediction exceeds budget if not provided
$predictedTotal = $currentData['expected'] ?? 0;
$exceedingPercentage = $exceedingPercentage ?? round(abs(($predictedTotal / $yearlyBudgetTarget * 100) - 100), 1);
$isExceedingBudget = $isExceedingBudget ?? ($predictedTotal > $yearlyBudgetTarget);
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
    <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
    <h3 class="text-lg font-semibold mb-4 dark:text-white">
    {{ $type === 'electricity' ? 'Elektriciteit' : 'Gas' }} Voorspelling 
    <span class="text-sm bg-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-100 text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-800 px-2 py-1 rounded ml-2 dark:bg-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-900/30 dark:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-300">
        {{ ucfirst($period) }}
    </span>
    
    @if(isset($currentMonthName) && $period === 'month')
    <span class="text-sm bg-gray-100 text-gray-800 px-2 py-1 rounded ml-2 dark:bg-gray-700 dark:text-gray-200">
        {{ $currentMonthName }}
    </span>
    @endif
</h3>
        
<div class="mb-4">
    <div class="flex justify-between items-center">
        <div class="flex items-center">
            <span class="text-sm text-gray-600 dark:text-gray-300">Betrouwbaarheid: </span>
            <div class="w-24 h-4 bg-gray-200 rounded-full ml-2 dark:bg-gray-700">
                <div class="h-4 rounded-full {{ $confidence > 80 ? 'bg-green-500' : ($confidence > 60 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                     style="width: {{ $confidence }}%"></div>
            </div>
            <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">{{ $confidence }}%</span>
        </div>
                
                <!-- Properly label percentage for clarity -->
        <div class="text-sm text-{{ $percentage <= 100 ? 'green' : 'red' }}-600 dark:text-{{ $percentage <= 100 ? 'green' : 'red' }}-400 font-medium">
            @if($period == 'year')
                Verbruik tot nu toe: {{ number_format($percentage, 1) }}%
            @else
                {{ $percentage > 100 ? 'Overschrijding' : 'Binnen budget' }}: {{ number_format(abs($percentage - 100), 1) }}%
            @endif
        </div>
    </div>
            
            <!-- Display budget info based on period -->
    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 flex justify-between">
        @if($period == 'month' && isset($monthlyBudgetValue))
            <span>Maandbudget: {{ number_format($monthlyBudgetValue, 0) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}</span>
            <span>Dagelijks budget: {{ number_format($monthlyBudgetValue / $daysInMonth, 1) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}/dag</span>
        @else
            <span>Jaarbudget: {{ number_format($yearlyBudgetTarget, 0) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}</span>
            <span>Maandbudget: {{ number_format($yearlyBudgetTarget / 12, 0) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}</span>
        @endif
    </div>
</div>
        
        <!-- Prediction Chart Canvas -->
        <div class="relative" style="height: 350px;">
            <canvas id="predictionChart{{ $type }}{{ $period }}"></canvas>
        </div>
        
        <!-- Predictions Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <!-- Expected Scenario Card -->
            <div class="bg-gray-50 p-4 rounded-lg dark:bg-gray-700">
                <h4 class="font-medium text-gray-700 mb-2 dark:text-white">Verwachte uitkomst</h4>
                <p class="text-2xl font-bold text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-600 dark:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-400">
                    {{ number_format($currentData['expected'], 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    @if($isExceedingBudget)
                        Overschrijding budget: {{ number_format($exceedingPercentage, 1) }}%
                    @else
                        Onder budget: {{ number_format($exceedingPercentage, 1) }}%
                    @endif
                </p>
            </div>
            
            <!-- Best Case Card -->
            <div class="bg-green-50 p-4 rounded-lg dark:bg-green-900/30">
                <h4 class="font-medium text-green-700 mb-2 dark:text-green-400">Best case scenario</h4>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($currentData['best_case'], 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $currentData['best_case'] > $yearlyBudgetTarget ? 'Overschrijding' : 'Onder' }} budget: 
                    {{ number_format(abs(($currentData['best_case'] / $yearlyBudgetTarget * 100) - 100), 1) }}%
                </p>
            </div>
            
            <!-- Worst Case Card -->
            <div class="bg-red-50 p-4 rounded-lg dark:bg-red-900/30">
                <h4 class="font-medium text-red-700 mb-2 dark:text-red-400">Worst case scenario</h4>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ number_format($currentData['worst_case'], 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $currentData['worst_case'] > $yearlyBudgetTarget ? 'Overschrijding' : 'Onder' }} budget: 
                    {{ number_format(abs(($currentData['worst_case'] / $yearlyBudgetTarget * 100) - 100), 1) }}%
                </p>
            </div>
        </div>
        
        <!-- Recommendations Based on Prediction -->
        <div class="mt-6 p-4 bg-{{ $isExceedingBudget ? 'red' : 'green' }}-50 rounded-lg dark:bg-{{ $isExceedingBudget ? 'red' : 'green' }}-900/30">
            <h4 class="font-medium text-{{ $isExceedingBudget ? 'red' : 'green' }}-700 dark:text-{{ $isExceedingBudget ? 'red' : 'green' }}-400 mb-2">
                {{ $isExceedingBudget ? 'Actie nodig' : 'Goed op weg' }}
            </h4>
            <p class="text-gray-700 dark:text-gray-300">
                @if($exceedingPercentage > 30 && $isExceedingBudget)
                    U zit momenteel significant boven uw jaarbudget. Overweeg maatregelen om uw {{ $type === 'electricity' ? 'elektriciteits' : 'gas' }}verbruik te verminderen.
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
</div>

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
        
        // Define colors for different energy types
        const mainColor = energyType === 'electricity' ? 
            'rgb(59, 130, 246)' : 'rgb(245, 158, 11)';
        const mainColorLight = energyType === 'electricity' ? 
            'rgba(59, 130, 246, 0.1)' : 'rgba(245, 158, 11, 0.1)';
        
        // Define x-axis labels based on period
        const labels = getLabels(periodType);
        
        // Prepare the chart data
        const ctx = document.getElementById(`predictionChart${energyType}${periodType}`).getContext('2d');
        
        // Calculate y-axis min/max based on the period type for proper scaling
        const yAxisConfig = getYAxisConfig(periodType, energyType, currentData, budgetData);
        
        // Create datasets
        const chartData = {
            labels: labels,
            datasets: [
                // Actual data
                {
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
                },
                // Expected prediction
                {
                    label: 'Verwachte trend',
                    data: currentData.prediction,
                    borderColor: 'rgba(107, 114, 128, 0.8)',
                    borderDash: [5, 5],
                    backgroundColor: 'rgba(107, 114, 128, 0.1)',
                    tension: 0.3,
                    fill: false,
                    pointRadius: 0,
                    borderWidth: 2,
                    order: 3
                },
                // Best case scenario
                {
                    label: 'Best case',
                    data: currentData.best_case_line,
                    borderColor: 'rgba(16, 185, 129, 0.6)',
                    borderDash: [5, 5],
                    tension: 0.3,
                    fill: false,
                    pointRadius: 0,
                    borderWidth: 2,
                    order: 4
                },
                // Worst case scenario
                {
                    label: 'Worst case',
                    data: currentData.worst_case_line,
                    borderColor: 'rgba(239, 68, 68, 0.6)',
                    borderDash: [5, 5],
                    tension: 0.3,
                    fill: false,
                    pointRadius: 0,
                    borderWidth: 2,
                    order: 5
                },
                // Budget target - transform to line appropriate for the period
                {
                    label: 'Budget',
                    data: getPeriodBudgetLine(periodType, budgetData),
                    borderColor: 'rgba(0, 0, 0, 0.7)',
                    backgroundColor: 'rgba(0, 0, 0, 0)',
                    borderWidth: 2.5,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0,
                    pointRadius: 0,
                    order: 1 // Place budget line behind other elements
                }
            ]
        };
        
        // Create prediction band (area between best and worst case)
        if (currentData.actual.length > 0) {
            const areaBetween = {
                type: 'line',
                label: 'Voorspellingsmarge',
                data: currentData.worst_case_line,
                backgroundColor: 'rgba(156, 163, 175, 0.2)', // Gray with transparency
                borderWidth: 0,
                tension: 0.3,
                fill: '+1',
                pointRadius: 0,
                order: 2 // Place prediction band behind most elements but above budget line
            };
            
            // Insert after the worst case dataset
            chartData.datasets.splice(4, 0, areaBetween);
        }
        
        // Adjust colors for dark mode
        if (isDarkMode) {
            chartData.datasets[4].borderColor = 'rgba(255, 255, 255, 0.7)'; // Budget line
        }
        
        // Create the chart
        const predictionChart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            footer: function(tooltipItems) {
                                const datasetIndex = tooltipItems[0].datasetIndex;
                                if (datasetIndex === 0) { // Actual data
                                    const index = tooltipItems[0].dataIndex;
                                    const budgetValue = getValueForTooltip(periodType, budgetData, index);
                                    const actualValue = currentData.actual[index] || 0;
                                    const percentage = budgetValue ? Math.round((actualValue / budgetValue) * 100) : 0;
                                    
                                    return `Budget: ${budgetValue.toFixed(2)} ${energyType === 'electricity' ? 'kWh' : 'm³'} (${percentage}%)`;
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
                }
            }
        });
        
        // Helper function to get budget line appropriate for the period
        
/**
 * Helper function om de budgetlijn te genereren op basis van de periode
 */
function getPeriodBudgetLine(period, budgetData) {
    const length = getLabels(period).length;
    
    // Controleer of we een array met budgetlijnwaarden hebben
    if (budgetData.line && Array.isArray(budgetData.line) && budgetData.line.length > 0) {
        // Voor jaarweergave, gebruik de daadwerkelijke maandelijkse budgetten
        if (period === 'year') {
            return budgetData.line;
        }
        // Voor dag- en maandweergave, we hebben al een array met correcte waarden
        else if (budgetData.line.length === length) {
            return budgetData.line;
        }
    }
    
    // Fallback naar de oude methode als er geen specifieke budgetlijn is
    switch(period) {
        case 'day':
            const dailyBudget = budgetData.per_unit || (budgetData.target / 365 / 24);
            return Array(length).fill(dailyBudget);
            
        case 'month':
            const daysInMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate();
            const monthlyBudget = budgetData.target / 12;
            const dailyBudgetValue = budgetData.per_unit || (monthlyBudget / daysInMonth);
            return Array(length).fill(dailyBudgetValue);
            
        case 'year':
        default:
            const monthlyBudgetValue = budgetData.per_unit || (budgetData.target / 12);
            return Array(12).fill(monthlyBudgetValue);
    }
}
        
        // Helper function to get budget value for tooltip
        function getValueForTooltip(period, budgetData, index) {
            switch(period) {
                case 'day':
                    return budgetData.target / 365 / 24; // Hourly budget
                case 'month':
                    return budgetData.target / 12 / new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate(); // Daily budget
                case 'year':
                default:
                    return budgetData.target / 12; // Monthly budget
            }
        }
        
        // Helper function to get y-axis configuration based on period
        function getYAxisConfig(period, energyType, currentData, budgetData) {
            // Find the maximum value in the data
            let maxActual = 0;
            let maxPrediction = 0;
            
            // Get budget value based on period
            let budgetMax = 0;
            switch(period) {
                case 'day':
                    budgetMax = budgetData.per_unit || (budgetData.target / 365 / 24); // Hourly budget
                    break;
                case 'month':
                    budgetMax = budgetData.per_unit || (budgetData.target / 12 / new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate()); // Daily budget
                    break;
                case 'year':
                default:
                    budgetMax = budgetData.per_unit || (budgetData.target / 12); // Monthly budget
                    break;
            }
            
            // Check actual data
            if (currentData.actual && currentData.actual.length > 0) {
                const actualValues = currentData.actual.filter(val => val !== null);
                maxActual = actualValues.length > 0 ? Math.max(...actualValues) : 0;
            }
            
            // Check all prediction lines
            const allPredictionData = [];
            
            // Add prediction data
            if (currentData.prediction && currentData.prediction.length > 0) {
                allPredictionData.push(...currentData.prediction.filter(val => val !== null));
            }
            
            // Add worst case scenario data
            if (currentData.worst_case_line && currentData.worst_case_line.length > 0) {
                allPredictionData.push(...currentData.worst_case_line.filter(val => val !== null));
            }
            
            maxPrediction = allPredictionData.length > 0 ? Math.max(...allPredictionData) : 0;
            
            // Find overall maximum
            const maxValue = Math.max(maxActual, maxPrediction, budgetMax);
            
            // Special handling for year view to prevent excessive scale
            if (period === 'year') {
                // For year view, use fixed scale based on typical monthly consumption
                // but ensure it's at least 20% above the highest actual value
                const yearMaximum = Math.max(maxActual * 1.2, maxPrediction * 1.05, 300);
                
                return {
                    min: 0,
                    max: Math.ceil(yearMaximum / 100) * 100, // Round to nearest 100
                    suggestedMax: Math.ceil(yearMaximum / 100) * 100
                };
            }
            
            // For day and month views, keep using the dynamic scaling approach
            // Set appropriate scaling for each view type
            switch(period) {
                case 'day':
                    // Day view (hourly data): typically 0-2 kWh per hour for electricity
                    return {
                        min: 0,
                        max: energyType === 'electricity' ? 2 : 1,
                        suggestedMax: energyType === 'electricity' ? 2 : 1
                    };
                case 'month':
                    // Month view (daily data): apply dynamic scaling for better visibility
                    // Ensure at least 30-50% padding above the maximum value
                    const maxMonthDisplay = Math.max(maxValue * 1.5, 
                                                energyType === 'electricity' ? 30 : 15);
                    return {
                        min: 0,
                        max: maxMonthDisplay,
                        suggestedMax: maxMonthDisplay
                    };
                default:
                    // Fallback - should not reach here
                    return {
                        min: 0,
                        max: maxValue * 1.2,
                        suggestedMax: maxValue * 1.2
                    };
            }
        }
        
        // Helper function to get scaled maximum value for better readability
        function getScaledMaximum(maxValue, period, energyType) {
            // Set realistic maximums based on period and energy type
            const maxLimits = {
                'electricity': {
                    'day': 2,      // Most households won't exceed 2 kWh per hour
                    'month': 30,   // Daily electricity typically under 30 kWh
                    'year': 500    // Monthly electricity typically under 500 kWh
                },
                'gas': {
                    'day': 1,      // Most households won't exceed 1 m³ per hour
                    'month': 15,   // Daily gas typically under 15 m³
                    'year': 250    // Monthly gas typically under 250 m³
                }
            };
            
            // Add padding (50% to ensure enough room)
            let paddedMax = maxValue * 1.5;
            
            // Don't exceed reasonable max values
            paddedMax = Math.min(paddedMax, maxLimits[energyType][period] || 500);
            
            // If the max is too small, use sensible minimums
            if (paddedMax < 0.1) {
                switch(period) {
                    case 'day':
                        paddedMax = energyType === 'electricity' ? 0.5 : 0.2;
                        break;
                    case 'month':
                        paddedMax = energyType === 'electricity' ? 15 : 8;
                        break;
                    case 'year':
                        paddedMax = energyType === 'electricity' ? 400 : 200;
                        break;
                }
            }
            
            // Round to a nice number based on scale
            let roundedMax;
            
            if (paddedMax < 1) {
                // For small values, round to nearest 0.1 or 0.5
                roundedMax = Math.ceil(paddedMax * 10) / 10;
            } else if (paddedMax < 10) {
                // For values under 10, round to nearest 1
                roundedMax = Math.ceil(paddedMax);
            } else if (paddedMax < 100) {
                // For values under 100, round to nearest 10
                roundedMax = Math.ceil(paddedMax / 10) * 10;
            } else if (paddedMax < 1000) {
                // For values under 1000, round to nearest 50
                roundedMax = Math.ceil(paddedMax / 50) * 50;
            } else {
                // For large values, round to nearest 500
                roundedMax = Math.ceil(paddedMax / 500) * 500;
            }
            
            return {
                max: roundedMax,
                suggestedMax: roundedMax
            };
        }
        
        // Helper functions for chart labels
        function getLabels(period) {
            switch(period) {
                case 'day':
                    return Array.from({length: 24}, (_, i) => `${i}:00`);
                case 'month':
                    const daysInMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).getDate();
                    return Array.from({length: daysInMonth}, (_, i) => `${i + 1}`);
                case 'year':
                default:
                    return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            }
        }
        
        function getPeriodLabel(period) {
            switch(period) {
                case 'day': return 'Uren';
                case 'month': return 'Dagen';
                case 'year': default: return 'Maanden';
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
            
            darkModeObserver.observe(document.documentElement, { attributes: true });
        };
        
        function updateChartColors() {
            const isDarkNow = document.documentElement.classList.contains('dark') || 
                            document.querySelector('html').classList.contains('dark') ||
                            window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            const newTextColor = isDarkNow ? '#FFFFFF' : '#000000';
            const newGridColor = isDarkNow ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            
            // Update budget line color for dark/light mode
            predictionChart.data.datasets[4].borderColor = isDarkNow ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';
            
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
        
        // Initialize theme watcher
        themeWatcher();
    });
</script>
@endpush