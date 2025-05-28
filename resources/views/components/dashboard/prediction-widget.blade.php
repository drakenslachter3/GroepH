@props(['title', 'type', 'predictionData', 'budgetData', 'period', 'date', 'confidence' => 75])

<section aria-labelledby="prediction-widget-title">
    <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
        <h3 tabindex="0" id="prediction-widget-title" class="text-lg font-semibold mb-4 dark:text-white">
            {{ $type === 'electricity' ? 'Elektriciteit' : 'Gas' }} Voorspelling 
            <span class="text-sm bg-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-100 text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-800 px-2 py-1 rounded ml-2 dark:bg-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-900/30 dark:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-300">
                {{ ucfirst($period) }}
            </span>
        </h3>
        <x-dashboard.widget-navigation :showPrevious="true" />
        <x-dashboard.widget-heading :title="$title" :period="$period" :date="$date" />
        <x-dashboard.widget-navigation :showNext="true" />
        
        <!-- Confidence Indicator
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
            </div>
        </div> -->
        
        <!-- Prediction Chart -->
        <div class="relative mb-4" style="height: 200px;">
            <canvas id="dashboardPredictionChart{{ $type }}"></canvas>
        </div>
        
        <!-- Scenario Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Expected Case -->
            <div class="bg-gray-50 p-3 rounded-lg dark:bg-gray-700">
                <div class="flex items-center justify-between">
                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-200">Verwacht</h5>
                    <span class="text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-600 dark:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-400 font-bold">
                        {{ number_format($predictionData['expected'], 1) }}
                        <span class="text-xs">{{ $type === 'electricity' ? 'kWh' : 'm³' }}</span>
                    </span>
                </div>
            </div>
            
            <!-- Best Case -->
            <div class="bg-green-50 p-3 rounded-lg dark:bg-green-900/30">
                <div class="flex items-center justify-between">
                    <h5 class="text-sm font-medium text-green-700 dark:text-green-300">Best case</h5>
                    <span class="text-green-600 dark:text-green-400 font-bold">
                        {{ number_format($predictionData['best_case'], 1) }}
                        <span class="text-xs">{{ $type === 'electricity' ? 'kWh' : 'm³' }}</span>
                    </span>
                </div>
            </div>
            
            <!-- Worst Case -->
            <div class="bg-red-50 p-3 rounded-lg dark:bg-red-900/30">
                <div class="flex items-center justify-between">
                    <h5 class="text-sm font-medium text-red-700 dark:text-red-300">Worst case</h5>
                    <span class="text-red-600 dark:text-red-400 font-bold">
                        {{ number_format($predictionData['worst_case'], 1) }}
                        <span class="text-xs">{{ $type === 'electricity' ? 'kWh' : 'm³' }}</span>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Link to full prediction page -->
        <div class="mt-4 text-center">
            <a href="{{ route('energy.predictions', ['type' => $type, 'period' => $period, 'date' => $date]) }}" 
               class="text-sm text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-600 hover:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-800 dark:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-400 dark:hover:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-300">
                Bekijk gedetailleerde voorspelling →
            </a>
        </div>
    </div>
</section>

@push('dashboard-prediction-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            return;
        }
        
        // Check dark mode
        const isDarkMode = document.documentElement.classList.contains('dark') || 
                         document.querySelector('html').classList.contains('dark') ||
                         window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Set text color based on dark mode
        const textColor = isDarkMode ? '#FFFFFF' : '#000000';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        
        // Get canvas and context
        const ctx = document.getElementById(`dashboardPredictionChart{{ $type }}`);
        if (!ctx) return;
        
        // Parse data from props
        const predictionData = @json($predictionData);
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
        
        // Create chart data
        const chartData = {
            labels: labels,
            datasets: [
                // Actual data
                {
                    label: 'Werkelijk',
                    data: predictionData.actual,
                    borderColor: mainColor,
                    backgroundColor: mainColorLight,
                    tension: 0.2,
                    fill: false,
                    pointRadius: 3,
                    pointBackgroundColor: mainColor,
                    borderWidth: 2,
                    order: 0
                },
                // Expected prediction
                {
                    label: 'Verwacht',
                    data: predictionData.prediction,
                    borderColor: 'rgba(107, 114, 128, 0.8)',
                    borderDash: [5, 5],
                    backgroundColor: 'rgba(107, 114, 128, 0.1)',
                    tension: 0.3,
                    fill: false,
                    pointRadius: 0,
                    borderWidth: 2,
                    order: 3
                },
                // Budget line
                {
                    label: 'Budget',
                    data: getPeriodBudgetLine(periodType, budgetData),
                    borderColor: isDarkMode ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)',
                    backgroundColor: 'rgba(0, 0, 0, 0)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0,
                    pointRadius: 0,
                    order: 1
                }
            ]
        };
        
        // Create prediction band (area between best and worst case)
        if (predictionData.best_case_line && predictionData.worst_case_line) {
            // Add best case line
            chartData.datasets.push({
                label: 'Best case',
                data: predictionData.best_case_line,
                borderColor: 'rgba(16, 185, 129, 0.6)',
                borderDash: [5, 5],
                tension: 0.3,
                fill: false,
                pointRadius: 0,
                borderWidth: 1.5,
                order: 4
            });
            
            // Add worst case line
            chartData.datasets.push({
                label: 'Worst case',
                data: predictionData.worst_case_line,
                borderColor: 'rgba(239, 68, 68, 0.6)',
                borderDash: [5, 5],
                tension: 0.3,
                fill: false,
                pointRadius: 0,
                borderWidth: 1.5,
                order: 5
            });
            
            // Add prediction area
            chartData.datasets.splice(3, 0, {
                type: 'line',
                label: 'Voorspellingsmarge',
                data: predictionData.worst_case_line,
                backgroundColor: 'rgba(156, 163, 175, 0.2)',
                borderWidth: 0,
                tension: 0.3,
                fill: '-1',
                pointRadius: 0,
                order: 2
            });
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
                            display: false
                        },
                        grid: {
                            color: gridColor,
                            display: false
                        },
                        ticks: {
                            color: textColor,
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 6
                        }
                    },
                    y: {
                        title: {
                            display: false
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
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
        
        // Helper function to get labels
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
        
        // Helper function to get budget line
        function getPeriodBudgetLine(period, budgetData) {
            const length = getLabels(period).length;
            
            // Check if we have a budget line array
            if (budgetData.line && Array.isArray(budgetData.line) && budgetData.line.length > 0) {
                if (period === 'year') {
                    return budgetData.line;
                }
                else if (budgetData.line.length === length) {
                    return budgetData.line;
                }
            }
            
            // Fallback to default budget calculation
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
        
        // Watch for theme changes
        const darkModeObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const isDarkNow = document.documentElement.classList.contains('dark');
                    
                    // Update chart colors
                    const newTextColor = isDarkNow ? '#FFFFFF' : '#000000';
                    const newGridColor = isDarkNow ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
                    
                    // Update budget line color
                    predictionChart.data.datasets[2].borderColor = isDarkNow ? 
                        'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';
                    
                    // Update scales colors
                    predictionChart.options.scales.x.ticks.color = newTextColor;
                    predictionChart.options.scales.y.ticks.color = newTextColor;
                    predictionChart.options.scales.x.grid.color = newGridColor;
                    predictionChart.options.scales.y.grid.color = newGridColor;
                    
                    // Update the chart
                    predictionChart.update();
                }
            });
        });
        
        // Start observing html for dark mode changes
        darkModeObserver.observe(document.documentElement, { attributes: true });
    });
</script>
@endpush