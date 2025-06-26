@props(['title', 'currentData', 'budgetData', 'type', 'period', 'percentage', 'confidence', 'yearlyConsumptionToDate' => 0, 'dailyAverageConsumption' => 0, 'date' => null, 'currentMonthName' => null, 'monthlyBudgetValue' => null, 'isInFuture' => true, 'unit' => null, 'realMeterData' => [], 'dataKey' => null])

{{-- Set default values for any missing props --}}
@php
use Carbon\Carbon;

// Ensure we have the correct unit
$unit = $unit ?? ($type === 'electricity' ? 'kWh' : 'm³');

$dataKey = $dataKey ?? ($type === 'electricity' ? 'energy_consumed' : 'gas_delivered');

// Use real meter data instead of prediction data for actual consumption
$actualMeterData = $realMeterData[$dataKey] ?? [];

// Calculate yearlyBudgetTarget if not provided
$yearlyBudgetTarget = $yearlyBudgetTarget ?? $budgetData['target'] ?? 0;

// Calculate if prediction exceeds budget if not provided
$predictedTotal = $currentData['expected'] ?? 0;

// Calculate the correct budget target based on period
$budgetTargetForPrediction = match($period) {
    'day' => $budgetData['monthly_target'] * 12 / 365, // Daily budget
    'month' => $budgetData['monthly_target'] ?? $monthlyBudgetValue ?? 0, // Monthly budget
    'year' => $yearlyBudgetTarget, // Yearly budget
    default => $yearlyBudgetTarget
};

$exceedingPercentage = $exceedingPercentage ?? ($predictedTotal > 0 ? round(abs(($predictedTotal / $budgetTargetForPrediction * 100) - 100), 1) : 0);
$isExceedingBudget = $isExceedingBudget ?? ($predictedTotal > $budgetTargetForPrediction);

// Parse the date to get proper period labels
$selectedDate = Carbon::parse($date);
$now = Carbon::now();

// Get period-specific consumption labels
$consumptionPeriodLabel = match($period) {
    'day' => $selectedDate->isToday() ? 'Verbruik vandaag' : 'Verbruik op ' . $selectedDate->translatedFormat('j F'),
    'month' => $selectedDate->isSameMonth($now) ? 'Verbruik deze maand' : 'Verbruik in ' . $selectedDate->translatedFormat('F Y'),
    'year' => $selectedDate->isSameYear($now) ? 'Verbruik dit jaar' : 'Verbruik in ' . $selectedDate->format('Y'),
    default => 'Verbruik'
};

// Get period-specific average labels
$averagePeriodLabel = match($period) {
    'day' => 'Gemiddeld per uur',
    'month' => 'Gemiddeld per dag',
    'year' => 'Gemiddeld per maand',
    default => 'Gemiddeld'
};

// Check if we should show predictions (only for current/future dates and if confidence exists)
$showPredictions = $isInFuture && ($confidence !== null && $confidence > 0);

// Create unique identifiers to prevent conflicts
$chartId = "predictionChart{$type}{$period}" . uniqid();
$totalPeriodId = "totalThisPeriod{$type}{$period}" . uniqid();
$averageConsumptionId = "averageConsumption{$type}{$period}" . uniqid();

// Calculate actual total from the data array using same logic as energy-chart
$actualTotal = 0;
if (is_array($actualMeterData)) {
    foreach ($actualMeterData as $value) {
        if ($value !== null && is_numeric($value)) {
            $actualTotal += $value;
        }
    }
}

// Always use the calculated actual total - don't fallback to yearly consumption
// If there's no consumption for the period, it should show 0, not yearly total
$displayTotal = $actualTotal;

// Calculate correct average based on period and actual data
$calculatedAverage = 0;
$averageUnit = '';

if ($period === 'day') {
    // For day view: average per hour
    $averageUnit = '/uur';
    if ($selectedDate->isToday()) {
        // If it's today, divide by hours elapsed
        $hoursElapsed = max(1, $now->hour + 1); // +1 because we include current hour
        $calculatedAverage = $hoursElapsed > 0 ? $displayTotal / $hoursElapsed : 0;
    } else {
        // For past/future days, divide by 24 hours
        $calculatedAverage = $displayTotal / 24;
    }
} elseif ($period === 'month') {
    // For month view: average per day
    $averageUnit = '/dag';
    if ($selectedDate->isSameMonth($now)) {
        // If it's current month, divide by days elapsed
        $daysElapsed = max(1, $now->day);
        $calculatedAverage = $daysElapsed > 0 ? $displayTotal / $daysElapsed : 0;
    } else {
        // For past/future months, divide by total days in that month
        $daysInMonth = $selectedDate->daysInMonth;
        $calculatedAverage = $daysInMonth > 0 ? $displayTotal / $daysInMonth : 0;
    }
} else {
    // For year view: average per month
    $averageUnit = '/maand';
    if ($selectedDate->isSameYear($now)) {
        // If it's current year, divide by months elapsed
        $monthsElapsed = max(1, $now->month);
        $calculatedAverage = $monthsElapsed > 0 ? $displayTotal / $monthsElapsed : 0;
    } else {
        // For past/future years, divide by 12 months
        $calculatedAverage = $displayTotal / 12;
    }
}

// FIX: Herbereken het percentage om ervoor te zorgen dat het correct is
$correctedPercentage = $percentage;

if ($period === 'year') {
    // Voor jaarweergave: verbruik tot nu toe / jaarbudget * 100
    $yearlyTarget = $budgetData['target'] ?? $yearlyBudgetTarget ?? 0;
    if ($yearlyTarget > 0) {
        $correctedPercentage = ($displayTotal / $yearlyTarget) * 100;
    }
} else {
    // Voor dag/maand weergave: gebruik de bestaande logica maar controleer of het klopt
    $targetForPeriod = match($period) {
        'day' => $budgetData['monthly_target'] * 12 / 365,
        'month' => $budgetData['monthly_target'] ?? $monthlyBudgetValue ?? 0,
        default => 0
    };
    
    if ($targetForPeriod > 0) {
        $correctedPercentage = ($displayTotal / $targetForPeriod) * 100;
    }
}
@endphp

<section aria-labelledby="prediction-chart-title-{{ $type }}-{{ $period }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
    {{-- Navigation and Heading Section --}}
    <x-dashboard.widget-navigation :showPrevious="true" aria-label="{{ __('energy-chart-widget.previous_widget') }}" />
    <x-dashboard.widget-heading :title="$title" :type="$type" :date="$date" :period="$period" />
    <x-dashboard.widget-navigation :showNext="true" aria-label="{{ __('energy-chart-widget.next_widget') }}" />
    <div class="py-6">
        @if($showPredictions)
            {{-- Confidence and Budget Status Section - Only show for predictions --}}
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
                            
                    <div class="text-sm text-{{ $correctedPercentage <= 100 ? 'green' : 'red' }}-600 dark:text-{{ $correctedPercentage <= 100 ? 'green' : 'red' }}-400 font-medium">
                        @if($period == 'year')
                            Verbruik tot nu toe: {{ number_format($correctedPercentage, 1) }}%
                        @else
                            {{ $correctedPercentage > 100 ? 'Overschrijding' : 'Binnen budget' }}: {{ number_format(abs($correctedPercentage - 100), 1) }}%
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    {{-- Chart Canvas --}}
    <div class="relative" style="height: 350px;">
        <canvas id="{{ $chartId }}"></canvas>
    </div>
    
    {{-- Budget Information --}}
    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 flex justify-between">
        @if($period == 'day')
            {{-- Day view: Show daily budget using the values from budgetData --}}
            <span>Dagelijks budget: {{ number_format($budgetData['monthly_target'] * 12 / 365, 1) }} {{ $unit }}/dag</span>
            <span>Uurlijks budget: {{ number_format(($budgetData['monthly_target'] * 12 / 365) / 24, 2) }} {{ $unit }}/uur</span>
        @elseif($period == 'month' && isset($monthlyBudgetValue))
            {{-- Month view: Show monthly budget --}}
            <span>Maandbudget: {{ number_format($monthlyBudgetValue, 0) }} {{ $unit }}</span>
            <span>Dagelijks budget: {{ number_format($monthlyBudgetValue / 30, 1) }} {{ $unit }}/dag</span>
        @else
            {{-- Year view: Show yearly budget --}}
            <span>Jaarbudget: {{ number_format($yearlyBudgetTarget, 0) }} {{ $unit }}</span>
            <span>Maandbudget: {{ number_format($yearlyBudgetTarget / 12, 0) }} {{ $unit }}/maand</span>
        @endif
    </div>

    {{-- Energy Consumption Details Section --}}
    <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2">
        <div>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $consumptionPeriodLabel }}:</span>
            <p class="text-sm font-medium text-gray-800 dark:text-white" id="{{ $totalPeriodId }}">
                {{ number_format($displayTotal, 1) }} {{ $unit }}
            </p>
        </div>
        <div>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $averagePeriodLabel }}:</span>
            <p class="text-sm font-medium text-gray-800 dark:text-white" id="{{ $averageConsumptionId }}">
                {{ number_format($calculatedAverage, 1) }} {{ $unit }}{{ $averageUnit }}
            </p>
        </div>
    </div>
    
    @if($showPredictions)
        {{-- Predictions Summary Cards - Only show for future dates --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            {{-- Expected Scenario Card --}}
            <div class="bg-gray-50 p-4 rounded-lg dark:bg-gray-700">
                <h4 class="font-medium text-gray-700 mb-2 dark:text-white">Verwachte uitkomst</h4>
                <p class="text-2xl font-bold text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-600 dark:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-400">
                    {{ number_format($currentData['expected'], 2) }} {{ $unit }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    @if($isExceedingBudget)
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
                    {{ number_format($currentData['best_case'], 2) }} {{ $unit }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $currentData['best_case'] > $budgetTargetForPrediction ? 'Overschrijding' : 'Onder' }} budget: 
                    {{ number_format(abs(($currentData['best_case'] / $budgetTargetForPrediction * 100) - 100), 1) }}%
                </p>
            </div>
            
            {{-- Worst Case Card --}}
            <div class="bg-red-50 p-4 rounded-lg dark:bg-red-900/30">
                <h4 class="font-medium text-red-700 mb-2 dark:text-red-400">Worst case scenario</h4>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ number_format($currentData['worst_case'], 2) }} {{ $unit }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $currentData['worst_case'] > $budgetTargetForPrediction ? 'Overschrijding' : 'Onder' }} budget: 
                    {{ number_format(abs(($currentData['worst_case'] / $budgetTargetForPrediction * 100) - 100), 1) }}%
                </p>
            </div>
        </div>
        
        {{-- Recommendations Based on Prediction --}}
        <div class="mt-6 p-4 bg-{{ $correctedPercentage <= 100 ? 'green' : 'red' }}-50 rounded-lg dark:bg-{{ $correctedPercentage <= 100 ? 'green' : 'red' }}-900/30">
            <h4 class="font-medium text-{{ $correctedPercentage <= 100 ? 'green' : 'red' }}-700 dark:text-{{ $correctedPercentage <= 100 ? 'green' : 'red' }}-400 mb-2">
                {{ $correctedPercentage > 100 ? 'Actie nodig' : 'Goed op weg' }}
            </h4>
            <p class="text-gray-700 dark:text-gray-300">
                @php
                    $exceedingAmount = abs($correctedPercentage - 100);
                @endphp
                @if($exceedingAmount > 30 && $correctedPercentage > 100)
                    U zit momenteel significant boven uw {{ $period === 'year' ? 'jaar' : ($period === 'month' ? 'maand' : 'dag') }}budget. Overweeg maatregelen om uw {{ $type === 'electricity' ? 'elektriciteits' : 'gas' }}verbruik te verminderen.
                @elseif($correctedPercentage > 100)
                    U zit momenteel iets boven uw {{ $period === 'year' ? 'jaar' : ($period === 'month' ? 'maand' : 'dag') }}budget. Let op uw verbruik om binnen het budget te blijven.
                @elseif($exceedingAmount < 10)
                    U zit goed op schema om binnen uw {{ $period === 'year' ? 'jaar' : ($period === 'month' ? 'maand' : 'dag') }}budget te blijven. Blijf uw verbruik in de gaten houden.
                @else
                    U zit goed op schema en gebruikt minder dan verwacht. Ga zo door!
                @endif
            </p>
        </div>
    @else
        {{-- Summary for past dates - just show actual vs budget --}}
        <div class="mt-6 p-4 bg-blue-50 rounded-lg dark:bg-blue-900/30">
            <h4 class="font-medium text-blue-700 dark:text-blue-400 mb-2">
                @if($selectedDate->isPast())
                    Verbruiksoverzicht
                @else
                    Huidig verbruik
                @endif
            </h4>
            <p class="text-gray-700 dark:text-gray-300">
                @php
                    $targetForPeriod = match($period) {
                        'day' => $budgetData['monthly_target'] * 12 / 365,
                        'month' => $budgetData['monthly_target'] ?? 0,
                        'year' => $budgetData['target'] ?? 0,
                        default => 0
                    };
                    $actualPercentage = $targetForPeriod > 0 ? ($displayTotal / $targetForPeriod) * 100 : 0;
                @endphp
                
                @if($actualPercentage <= 100)
                    Het verbruik was {{ number_format($actualPercentage, 1) }}% van het budget ({{ number_format($displayTotal, 1) }} van {{ number_format($targetForPeriod, 1) }} {{ $unit }}).
                @else
                    Het verbruik was {{ number_format($actualPercentage - 100, 1) }}% boven het budget ({{ number_format($displayTotal, 1) }} van {{ number_format($targetForPeriod, 1) }} {{ $unit }}).
                @endif
            </p>
        </div>
    @endif
</section>

@push('prediction-chart-scripts')
<script>
    // Use IIFE (Immediately Invoked Function Expression) to avoid global scope conflicts
    (function() {
        'use strict';
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initChart);
        } else {
            initChart();
        }
        
        function initChart() {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded!');
                return;
            }
            
            // All variables are scoped to this function to prevent conflicts
            const chartId = '{{ $chartId }}';
            const totalPeriodId = '{{ $totalPeriodId }}';
            const averageConsumptionId = '{{ $averageConsumptionId }}';
            
            // Check if dark mode is active
            const isDarkMode = document.documentElement.classList.contains('dark') || 
                              document.querySelector('html').classList.contains('dark') ||
                              window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            // Set the text color based on dark mode
            const textColor = isDarkMode ? '#FFFFFF' : '#000000';
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            
            // Parse data from PHP props - these are unique per chart instance
            const currentData = @json($currentData);
            const budgetData = @json($budgetData);
            const energyType = @json($type);
            const periodType = @json($period);
            const showPredictions = @json($showPredictions);
            const unitFromPHP = @json($unit);
            const displayTotal = @json($displayTotal);
            const calculatedAverage = @json($calculatedAverage);
            const averageUnit = @json($averageUnit);
            
            console.log(`Chart setup for ${energyType} ${periodType} with ID: ${chartId}`);
            console.log("Unit from PHP:", unitFromPHP);
            console.log("Current data keys:", Object.keys(currentData));
            console.log("Display total:", displayTotal);
            console.log("Calculated average:", calculatedAverage, averageUnit);
            
            // Define colors for different energy types
            const mainColor = energyType === 'electricity' ? 
                'rgb(59, 130, 246)' : 'rgb(245, 158, 11)';
            const mainColorLight = energyType === 'electricity' ? 
                'rgba(59, 130, 246, 0.1)' : 'rgba(245, 158, 11, 0.1)';
            
            // Define x-axis labels based on period
            const labels = getLabels(periodType);
            
            // Prepare the chart data
            const ctx = document.getElementById(chartId);
            if (!ctx) {
                console.error(`Chart canvas not found: ${chartId}`);
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
            // Actual data - use REAL meter data, not prediction data
           const realMeterData = @json($actualMeterData);
console.log('Original realMeterData:', realMeterData);

if (Array.isArray(realMeterData)) {
    // Krijg de geselecteerde datum van PHP
    const selectedDate = new Date(@json($selectedDate)); // PHP Carbon datum
    const currentDate = new Date();
    
    let needsCutoff = false;
    let currentPosition = -1;
    
    // Bepaal of we een cutoff nodig hebben (alleen voor huidige periode)
    if (periodType === 'day') {
        // Alleen cutoff als het vandaag is
        needsCutoff = selectedDate.toDateString() === currentDate.toDateString();
        if (needsCutoff) {
            currentPosition = currentDate.getHours();
        }
    } else if (periodType === 'month') {
        // Alleen cutoff als het de huidige maand is
        needsCutoff = selectedDate.getFullYear() === currentDate.getFullYear() && 
                     selectedDate.getMonth() === currentDate.getMonth();
        if (needsCutoff) {
            currentPosition = currentDate.getDate() - 1; // 0-indexed
        }
    } else { // year
        // Alleen cutoff als het het huidige jaar is
        needsCutoff = selectedDate.getFullYear() === currentDate.getFullYear();
        if (needsCutoff) {
            currentPosition = currentDate.getMonth(); // 0-indexed
        }
    }
    
    console.log(`Period: ${periodType}, needs cutoff: ${needsCutoff}, position: ${currentPosition}`);
    
    let processedData;
    let lastValidIndex = -1;
    
    if (needsCutoff) {
        // Voor huidige periode: knip af bij huidige positie
        processedData = realMeterData
            .slice(0, currentPosition + 1)
            .map(value => {
                if (value === null || value === undefined || isNaN(value)) {
                    return null;
                }
                return Number(value);
            });
    } else {
        // Voor andere periodes: toon alle beschikbare data
        processedData = realMeterData.map(value => {
            if (value === null || value === undefined || isNaN(value)) {
                return null;
            }
            return Number(value);
        });
    }
    
    console.log('Processed data:', processedData);
    
    // Vind het laatste geldige datapunt (inclusief 0)
    for (let i = processedData.length - 1; i >= 0; i--) {
        if (processedData[i] !== null && processedData[i] !== undefined) {
            lastValidIndex = i;
            break;
        }
    }
    
    console.log('Last valid index:', lastValidIndex);
    
    // Alleen voeg dataset toe als er werkelijke data is
    if (lastValidIndex >= 0) {
        // Sla de laatste geldige index en waarde op voor voorspellingslijnen
        window.lastDataPoint = {
            index: lastValidIndex,
            value: processedData[lastValidIndex]
        };
        
        chartData.datasets.push({
            label: 'Werkelijk verbruik',
            data: processedData,
            borderColor: mainColor,
            backgroundColor: mainColorLight,
            tension: 0.2,
            fill: false,
            pointRadius: function(context) {
                const value = context.parsed.y;
                return (value === null || value === undefined) ? 0 : 4;
            },
            pointHoverRadius: function(context) {
                const value = context.parsed.y;
                return (value === null || value === undefined) ? 0 : 6;
            },
            pointBackgroundColor: function(context) {
                const value = context.parsed.y;
                return (value === null || value === undefined) ? 'transparent' : mainColor;
            },
            borderWidth: 3,
            order: 0,
            spanGaps: false
        });
    }
}
            
            // Budget line - always show this
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
            
            // Only add prediction datasets if we should show predictions
            if (showPredictions) {
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
                                                const percentage = Math.round((actualValue / budgetValue) * 100);
                                                return `Budget: ${budgetValue.toFixed(2)} ${unitFromPHP} (${percentage}%)`;
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
                                text: `${energyType === 'electricity' ? 'Elektriciteit' : 'Gas'} (${unitFromPHP})`,
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
            
            // Update energy statistics display with real data (using unique IDs)
            updateEnergyStatistics();
            
            // Helper function to get budget line appropriate for the period
            function getPeriodBudgetLine(period, budgetData) {
                const length = getLabels(period).length;
                
                // Check if we have an array with budget line values
                if (budgetData.line && Array.isArray(budgetData.line) && budgetData.line.length > 0) {
                    console.log("Using provided budget line with length", budgetData.line.length);
                    // For year view, use the actual monthly budgets
                    if (period === 'year') {
                        return budgetData.line;
                    }
                    // For day and month view, we already have an array with correct values
                    else if (budgetData.line.length === length) {
                        return budgetData.line;
                    }
                }
                
                console.log("Generating fallback budget line for", period);
                // Fallback to the old method if there's no specific budget line
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
                
                // Check actual data (filter out null values)
                if (currentData.actual && Array.isArray(currentData.actual) && currentData.actual.length > 0) {
                    const actualValues = currentData.actual.filter(val => val !== null && val !== undefined);
                    maxActual = actualValues.length > 0 ? Math.max(...actualValues) : 0;
                }
                
                // Check all prediction lines only if we're showing predictions
                const allPredictionData = [];
                
                if (showPredictions) {
                    // Add prediction data
                    if (currentData.prediction && Array.isArray(currentData.prediction) && currentData.prediction.length > 0) {
                        allPredictionData.push(...currentData.prediction.filter(val => val !== null && val !== undefined));
                    }
                    
                    // Add worst case scenario data
                    if (currentData.worst_case_line && Array.isArray(currentData.worst_case_line) && currentData.worst_case_line.length > 0) {
                        allPredictionData.push(...currentData.worst_case_line.filter(val => val !== null && val !== undefined));
                    }
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
            
           // Helper functions for chart labels
function getLabels(period) {
    switch(period) {
        case 'day':
            return Array.from({length: 24}, (_, i) => `${i}:00`);
        case 'month':
            // *** FIX: Gebruik de geselecteerde datum in plaats van huidige datum ***
            const selectedDate = new Date(@json($selectedDate->format('Y-m-d')));
            const selectedYear = selectedDate.getFullYear();
            const selectedMonth = selectedDate.getMonth(); // 0-based (januari = 0)
            const daysInSelectedMonth = new Date(selectedYear, selectedMonth + 1, 0).getDate();
            return Array.from({length: daysInSelectedMonth}, (_, i) => `${i + 1}`);
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
            
            function updateEnergyStatistics() {
                // Find and update the elements using unique IDs and consistent calculation
                const periodConsumptionElement = document.getElementById(totalPeriodId);
                const averageConsumptionElement = document.getElementById(averageConsumptionId);
                
                if (periodConsumptionElement) {
                    periodConsumptionElement.textContent = `${Number(displayTotal).toFixed(1)} ${unitFromPHP}`;
                }
                
                if (averageConsumptionElement) {
                    averageConsumptionElement.textContent = `${Number(calculatedAverage).toFixed(1)} ${unitFromPHP}${averageUnit}`;
                }
            }
            
            // Watch for theme changes
            function setupThemeWatcher() {
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
            }
            
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
            
            // Initialize theme watcher
            setupThemeWatcher();
        }
    })(); // End of IIFE
</script>
@endpush