@props(['currentData', 'budgetData', 'type', 'period', 'percentage', 'confidence'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
    <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
        <h3 class="text-lg font-semibold mb-4 dark:text-white">
            {{ $type === 'electricity' ? 'Elektriciteit' : 'Gas' }} Voorspelling 
            <span class="text-sm bg-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-100 text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-800 px-2 py-1 rounded ml-2 dark:bg-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-900/30 dark:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-300">
                {{ ucfirst($period) }}
            </span>
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
                <div class="text-sm text-{{ $percentage > 100 ? 'red' : 'green' }}-600 dark:text-{{ $percentage > 100 ? 'red' : 'green' }}-400 font-medium">
                    {{ $percentage > 100 ? 'Overschrijding' : 'Binnen budget' }}: {{ number_format(abs($percentage - 100), 1) }}%
                </div>
            </div>
        </div>
        
        <!-- Prediction Chart Canvas -->
        <div class="relative" style="height: 300px;">
            <canvas id="predictionChart{{ $type }}"></canvas>
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
                    {{ $currentData['expected'] > $budgetData['target'] ? 'Overschrijding' : 'Onder' }} budget: 
                    {{ number_format(abs(($currentData['expected'] / $budgetData['target'] * 100) - 100), 1) }}%
                </p>
            </div>
            
            <!-- Best Case Card -->
            <div class="bg-green-50 p-4 rounded-lg dark:bg-green-900/30">
                <h4 class="font-medium text-green-700 mb-2 dark:text-green-400">Best case scenario</h4>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($currentData['best_case'], 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $currentData['best_case'] > $budgetData['target'] ? 'Overschrijding' : 'Onder' }} budget: 
                    {{ number_format(abs(($currentData['best_case'] / $budgetData['target'] * 100) - 100), 1) }}%
                </p>
            </div>
            
            <!-- Worst Case Card -->
            <div class="bg-red-50 p-4 rounded-lg dark:bg-red-900/30">
                <h4 class="font-medium text-red-700 mb-2 dark:text-red-400">Worst case scenario</h4>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ number_format($currentData['worst_case'], 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $currentData['worst_case'] > $budgetData['target'] ? 'Overschrijding' : 'Onder' }} budget: 
                    {{ number_format(abs(($currentData['worst_case'] / $budgetData['target'] * 100) - 100), 1) }}%
                </p>
            </div>
        </div>
        
        <!-- Recommendations Based on Prediction -->
        <div class="mt-6 p-4 bg-{{ $percentage > 100 ? 'red' : 'green' }}-50 rounded-lg dark:bg-{{ $percentage > 100 ? 'red' : 'green' }}-900/30">
            <h4 class="font-medium text-{{ $percentage > 100 ? 'red' : 'green' }}-700 dark:text-{{ $percentage > 100 ? 'red' : 'green' }}-400 mb-2">
                {{ $percentage > 100 ? 'Actie nodig' : 'Goed op weg' }}
            </h4>
            <p class="text-gray-700 dark:text-gray-300">
                @if($percentage > 110)
                    U zit momenteel significant boven uw jaarbudget. Overweeg maatregelen om uw {{ $type === 'electricity' ? 'elektriciteits' : 'gas' }}verbruik te verminderen.
                @elseif($percentage > 100)
                    U zit momenteel iets boven uw jaarbudget. Let op uw verbruik om binnen het budget te blijven.
                @elseif($percentage > 90)
                    U zit op schema om binnen uw jaarbudget te blijven. Blijf uw verbruik in de gaten houden.
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
        const ctx = document.getElementById(`predictionChart${energyType}`).getContext('2d');
        
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
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: mainColor
                },
                // Expected prediction
                {
                    label: 'Verwachte trend',
                    data: currentData.prediction,
                    borderColor: 'rgba(107, 114, 128, 0.8)',
                    borderDash: [5, 5],
                    backgroundColor: 'rgba(107, 114, 128, 0.1)',
                    tension: 0.4,
                    fill: false,
                    pointRadius: 0
                },
                // Best case scenario
                {
                    label: 'Best case',
                    data: currentData.best_case_line,
                    borderColor: 'rgba(16, 185, 129, 0.6)',
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                    pointRadius: 0
                },
                // Worst case scenario
                {
                    label: 'Worst case',
                    data: currentData.worst_case_line,
                    borderColor: 'rgba(239, 68, 68, 0.6)',
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                    pointRadius: 0
                },
                // Budget target
                {
                    label: 'Budget',
                    data: budgetData.line,
                    borderColor: 'rgba(0, 0, 0, 0.7)',
                    backgroundColor: 'rgba(0, 0, 0, 0)',
                    borderWidth: 2,
                    borderDash: [3, 3],
                    fill: false,
                    tension: 0,
                    pointRadius: 0
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
                tension: 0.4,
                fill: '+1',
                pointRadius: 0
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
                            color: textColor
                        },
                        beginAtZero: true
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
                                    const budgetValue = budgetData.values[index] || 0;
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