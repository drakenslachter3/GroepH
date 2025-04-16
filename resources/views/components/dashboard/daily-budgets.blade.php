@props(['initialBudgets' => null, 'initialMargin' => 15])

@php
use Carbon\Carbon;

// Get current date information
$today = Carbon::now()->day;
$month = Carbon::now()->month;
$year = Carbon::now()->year;
$daysInMonth = Carbon::now()->daysInMonth;

// Dutch month names
$monthNames = ['Januari','Februari','Maart','April','Mei','Juni','Juli','Augustus','September','Oktober','November','December'];

// Calculate remaining days in month
$remainingDaysInMonth = [];
for ($i = $today; $i <= $daysInMonth; $i++) {
    $remainingDaysInMonth[] = $i;
}

// Initial data for charts
$gasData = [];
$electricityData = [];
$gasMargin = $initialMargin;
$electricityMargin = $initialMargin;

// If no initial budgets provided, generate them
if (!$initialBudgets) {
    $gasData = generateSmoothBudgets($daysInMonth, 20, 50, $gasMargin);
    $electricityData = generateSmoothBudgets($daysInMonth, 20, 50, $electricityMargin);
} else {
    $gasData = $initialBudgets['gas'] ?? generateSmoothBudgets($daysInMonth, 20, 50, $gasMargin);
    $electricityData = $initialBudgets['electricity'] ?? generateSmoothBudgets($daysInMonth, 20, 50, $electricityMargin);
}

/**
 * Generate smooth budgets with a maximum difference defined by margin
 * 
 * @param int $days Number of days
 * @param int $min Minimum budget value
 * @param int $max Maximum budget value
 * @param int $margin Maximum percentage difference between consecutive days
 * @return array Array of daily budgets
 */
function generateSmoothBudgets($days, $min, $max, $margin) {
    $budgets = [];
    
    // Start with a random value within range
    $budgets[0] = rand($min, $max);
    
    // Generate remaining budgets with margin constraint
    for ($i = 1; $i < $days; $i++) {
        $prevBudget = $budgets[$i-1];
        $maxChange = ceil($prevBudget * ($margin / 100));
        
        // Calculate min and max possible values for this day
        $dayMin = max($min, $prevBudget - $maxChange);
        $dayMax = min($max, $prevBudget + $maxChange);
        
        // Generate value within acceptable range
        $budgets[$i] = rand($dayMin, $dayMax);
    }
    
    return $budgets;
}

// JSON encode data for JavaScript
$gasDataJson = json_encode($gasData);
$electricityDataJson = json_encode($electricityData);
@endphp

<div class="p-4 dark:bg-gray-800">
    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">Dagelijkse budgetten</h2>

    <div class="flex flex-col md:flex-row w-full gap-8">
        <!-- Gas section -->
        <div class="w-full md:w-1/2 p-4 rounded-lg shadow">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">Gas</h2>
            <div class="h-64">
                <canvas id="gas-chart"></canvas>
            </div>
            
            <!-- Gas margin controls -->
            <button onclick="toggleMargeSection('gas')" class="flex items-center text-left focus:outline-none mt-6 mb-2 w-full">
                <h2 class="text font-semibold text-gray-800 dark:text-gray-200">Marge aanpassen</h2>
                <svg id="gas-arrow" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 transform transition-transform duration-200 text-gray-700 dark:text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            
            <div id="marge-section-gas" class="hidden mb-6 p-4 bg-gray-50 dark:bg-gray-600 rounded-lg">
                <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                    <div class="flex flex-col items-center justify-center w-full md:w-1/4">
                        <input type="range" min="5" max="30" value="{{ $gasMargin }}" id="gas-margin-slider" class="vertical-slider mb-4 dark:bg-gray-800">
                        <div class="flex items-center gap-2">
                            <p class="text-sm text-gray-700 dark:text-gray-300"><span id="gas-margin-text">{{ $gasMargin }}</span>%</p>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">marge</p>

                        <button id="save-gas-margin" class="py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                            Opslaan
                        </button>
                    </div>

                    <div class="space-y-3 md:w-3/4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">Voor gas worden onderstaande marges aangeraden:</p>
                        <div class="flex flex-col gap-2 text-sm">
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                                <span class="text-gray-700 dark:text-gray-300">Hoog (25%+)</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                                <span class="text-gray-700 dark:text-gray-300">Medium (15-25%)</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full bg-green-400"></span>
                                <span class="text-gray-700 dark:text-gray-300">Laag (5-15%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    De ingestelde marge zorgt ervoor dat uw dag budgetten niet meer dan <span class="font-semibold">{{ $gasMargin }}%</span> van elkaar verschillen, wat zorgt voor een voorspelbare en geleidelijke verdeling van uw gasverbruik.
                </p>
            </div>
        </div>

        <!-- Electricity section -->
        <div class="w-full md:w-1/2 p-4 rounded-lg shadow mt-6 md:mt-0">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">Elektriciteit</h2>
            <div class="h-64">
                <canvas id="electricity-chart"></canvas>
            </div>
            
            <!-- Electricity margin controls -->
            <button onclick="toggleMargeSection('electricity')" class="flex items-center text-left focus:outline-none mt-6 mb-2 w-full">
                <h2 class="text font-semibold text-gray-800 dark:text-gray-200">Marge aanpassen</h2>
                <svg id="electricity-arrow" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 transform transition-transform duration-200 text-gray-700 dark:text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            
            <div id="marge-section-electricity" class="hidden mb-6 p-4 bg-gray-50 dark:bg-gray-600 rounded-lg">
                <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                    <div class="flex flex-col items-center justify-center w-full md:w-1/4">
                        <input type="range" min="5" max="30" value="{{ $electricityMargin }}" id="electricity-margin-slider" class="vertical-slider mb-4 dark:bg-gray-800">
                        <div class="flex items-center gap-2">
                            <p class="text-sm text-gray-700 dark:text-gray-300"><span id="electricity-margin-text">{{ $electricityMargin }}</span>%</p>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">marge</p>

                        <button id="save-electricity-margin" class="py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                            Opslaan
                        </button>
                    </div>

                    <div class="space-y-3 md:w-3/4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">Voor elektriciteit worden onderstaande marges aangeraden:</p>
                        <div class="flex flex-col gap-2 text-sm">
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                                <span class="text-gray-700 dark:text-gray-300">Hoog (20%+)</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                                <span class="text-gray-700 dark:text-gray-300">Medium (10-20%)</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full bg-green-400"></span>
                                <span class="text-gray-700 dark:text-gray-300">Laag (5-10%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    De ingestelde marge zorgt ervoor dat uw dag budgetten niet meer dan <span class="font-semibold">{{ $electricityMargin }}%</span> van elkaar verschillen, wat zorgt voor een voorspelbare en geleidelijke verdeling van uw elektriciteitsverbruik.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .vertical-slider {
        -webkit-appearance: slider-vertical;
        writing-mode: bt-lr;
        width: 8px;
        height: 100px;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Initial data from PHP
    const daysInMonth = {{ $daysInMonth }};
    const today = {{ $today }};
    let gasData = {{ $gasDataJson }};
    let electricityData = {{ $electricityDataJson }};
    let gasMargin = {{ $gasMargin }};
    let electricityMargin = {{ $electricityMargin }};
    
    // Chart references
    let gasChart;
    let electricityChart;
    
    // UI element references
    const gasMarginSlider = document.getElementById('gas-margin-slider');
    const gasMarginText = document.getElementById('gas-margin-text');
    const electricityMarginSlider = document.getElementById('electricity-margin-slider');
    const electricityMarginText = document.getElementById('electricity-margin-text');
    
    // Date labels for x-axis
    const month = {{ $month }};
    const year = {{ $year }};
    let dateLabels = [];
    for (let day = 1; day <= daysInMonth; day++) {
        dateLabels.push(`${day}-${month}`);
    }
    
    // Event listeners for sliders
    gasMarginSlider.addEventListener('input', () => {
        gasMarginText.innerHTML = gasMarginSlider.value;
    });
    
    electricityMarginSlider.addEventListener('input', () => {
        electricityMarginText.innerHTML = electricityMarginSlider.value;
    });
    
    // Toggle visibility functions
    function toggleMargeSection(type) {
        const section = document.getElementById(`marge-section-${type}`);
        section.classList.toggle('hidden');
        
        // Close other sections to avoid clutter
        if (type === 'gas') {
            document.getElementById('marge-section-electricity').classList.add('hidden');
            document.getElementById('budget-section-gas').classList.add('hidden');
        } else {
            document.getElementById('marge-section-gas').classList.add('hidden');
            document.getElementById('budget-section-electricity').classList.add('hidden');
        }
    }
    
    function toggleBudgetSection(type) {
        const section = document.getElementById(`budget-section-${type}`);
        section.classList.toggle('hidden');
        
        // Close other sections to avoid clutter
        if (type === 'gas') {
            document.getElementById('budget-section-electricity').classList.add('hidden');
            document.getElementById('marge-section-gas').classList.add('hidden');
        } else {
            document.getElementById('budget-section-gas').classList.add('hidden');
            document.getElementById('marge-section-electricity').classList.add('hidden');
        }
    }
    
    // Initialize charts on load
    document.addEventListener('DOMContentLoaded', function() {
        initializeCharts();
        setupEventListeners();
    });
    
    function initializeCharts() {
        // Chart options
        const baseOptions = (yLabel) => ({
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.raw;
                            return `${label}: ${value}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Dagen',
                        font: {
                            size: 14
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: yLabel,
                        font: {
                            size: 14
                        }
                    }
                }
            }
        });
        
        // Gas Chart
        const gasCtx = document.getElementById('gas-chart').getContext('2d');
        gasChart = new Chart(gasCtx, {
            type: 'bar',
            data: {
                labels: dateLabels,
                datasets: [{
                    label: 'budget in m³',
                    data: gasData,
                    backgroundColor: 'rgba(245, 158, 11, 0.5)',
                    borderColor: 'rgb(245, 158, 11)',
                    borderWidth: 1
                }]
            },
            options: {
                ...baseOptions('Dagbudgetten (m³)'),
                plugins: {
                    legend: {
                        labels: {
                            color: 'white'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Dagbudgetten (m³)',
                        color: 'white'
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: 'white'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.2)'
                        }
                    },
                    y: {
                        ticks: {
                            color: 'white'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.2)'
                        }
                    }
                }
            }
        });
        
        // Electricity Chart
        const electricityCtx = document.getElementById('electricity-chart').getContext('2d');
        electricityChart = new Chart(electricityCtx, {
            type: 'bar',
            data: {
                labels: dateLabels,
                datasets: [{
                    label: 'budget in kWh',
                    data: electricityData,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                ...baseOptions('Dagbudgetten (kWh)'),
                plugins: {
                    legend: {
                        labels: {
                            color: 'white'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Dagbudgetten (kWh)',
                        color: 'white'
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: 'white'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.2)'
                        }
                    },
                    y: {
                        ticks: {
                            color: 'white'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.2)'
                        }
                    }
                }
            }
        });
    }
    
    function setupEventListeners() {
        // Save margin buttons
        document.getElementById('save-gas-margin').addEventListener('click', () => {
            saveMargin('gas');
        });
        
        document.getElementById('save-electricity-margin').addEventListener('click', () => {
            saveMargin('electricity');
        });
        
        // Save budget buttons
        document.getElementById('update-gas-budgets').addEventListener('click', () => {
            saveBudgets('gas');
        });
        
        document.getElementById('update-electricity-budgets').addEventListener('click', () => {
            saveBudgets('electricity');
        });
    }
    
    // Generate smooth budgets with margin constraint
    function generateSmoothBudgets(days, min, max, margin, startingBudget = null) {
        const budgets = [];
        
        // Start with a provided value or random value
        budgets[0] = startingBudget !== null ? startingBudget : Math.floor(Math.random() * (max - min) + min);
        
        // Generate remaining budgets with margin constraint
        for (let i = 1; i < days; i++) {
            const prevBudget = budgets[i-1];
            const maxChange = Math.ceil(prevBudget * (margin / 100));
            
            // Calculate min and max possible values for this day
            const dayMin = Math.max(min, prevBudget - maxChange);
            const dayMax = Math.min(max, prevBudget + maxChange);
            
            // Generate value within acceptable range
            budgets[i] = Math.floor(Math.random() * (dayMax - dayMin + 1) + dayMin);
        }
        
        return budgets;
    }
    
    // Save margin and regenerate budgets
    function saveMargin(type) {
        // Get current margin value
        const margin = type === 'gas' 
            ? parseInt(gasMarginSlider.value) 
            : parseInt(electricityMarginSlider.value);
            
        // Save margin to state
        if (type === 'gas') {
            gasMargin = margin;
        } else {
            electricityMargin = margin;
        }
        
        // Send to backend
        sendMarginToBackend(type, margin);
        
        // Generate new budgets with the margin constraint
        const min = 20;
        const max = 50;
        const existingBudgets = type === 'gas' ? gasData : electricityData;
        const todayIndex = today - 1;
        
        // Use today's budget as starting point
        const todayBudget = existingBudgets[todayIndex];
        
        // Generate budgets for remaining days
        const newBudgets = generateSmoothBudgets(
            daysInMonth - todayIndex,
            min, 
            max, 
            margin, 
            todayBudget
        );
        
        // Update the budget values in the UI inputs
        for (let i = today; i <= daysInMonth; i++) {
            const dayInput = document.getElementById(`${type}-day-${i}`);
            if (dayInput) {
                dayInput.value = newBudgets[i - today];
            }
        }
        
        // Update chart data
        updateChartData(type, newBudgets);
        
        // Show notification
        showNotification(`${type === 'gas' ? 'Gas' : 'Elektriciteit'} marge aangepast naar ${margin}%`);
        
        // Close margin section
        document.getElementById(`marge-section-${type}`).classList.add('hidden');
    }
    
    // Save manually edited budgets
    function saveBudgets(type) {
        const inputs = document.querySelectorAll(`.${type}-budget-input`);
        const newBudgets = [];
        const margin = type === 'gas' ? gasMargin : electricityMargin;
        let validBudgets = true;
        
        // Collect all budget values
        inputs.forEach((input, index) => {
            newBudgets.push(parseInt(input.value));
        });
        
        // Validate budgets comply with margin
        for (let i = 1; i < newBudgets.length; i++) {
            const prev = newBudgets[i-1];
            const current = newBudgets[i];
            const maxChange = Math.ceil(prev * (margin / 100));
            
            if (Math.abs(current - prev) > maxChange) {
                validBudgets = false;
                break;
            }
        }
        
        if (!validBudgets) {
            showError(`Een of meer budgetten overschrijden de ${margin}% marge. Pas de waarden aan of verhoog de marge.`);
            return;
        }
        
        // Update chart data
        const startDay = today - 1;
        if (type === 'gas') {
            for (let i = 0; i < newBudgets.length; i++) {
                gasData[startDay + i] = newBudgets[i];
            }
            gasChart.data.datasets[0].data = gasData;
            gasChart.update();
        } else {
            for (let i = 0; i < newBudgets.length; i++) {
                electricityData[startDay + i] = newBudgets[i];
            }
            electricityChart.data.datasets[0].data = electricityData;
            electricityChart.update();
        }
        
        // Send to backend
        sendBudgetsToBackend(type, newBudgets);
        
        // Show notification
        showNotification(`${type === 'gas' ? 'Gas' : 'Elektriciteit'} budgetten opgeslagen`);
        
        // Close budget section
        document.getElementById(`budget-section-${type}`).classList.add('hidden');
    }
    
    // Update chart with new data
    function updateChartData(type, newBudgets) {
        const startDay = today - 1;
        
        if (type === 'gas') {
            // Update gas data and chart
            for (let i = 0; i < newBudgets.length; i++) {
                gasData[startDay + i] = newBudgets[i];
            }
            gasChart.data.datasets[0].data = gasData;
            gasChart.update();
        } else {
            // Update electricity data and chart
            for (let i = 0; i < newBudgets.length; i++) {
                electricityData[startDay + i] = newBudgets[i];
            }
            electricityChart.data.datasets[0].data = electricityData;
            electricityChart.update();
        }
    }
    
    // Send margin setting to backend
    function sendMarginToBackend(type, margin) {
        fetch('/api/energy-budget/margin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                type: type,
                margin: margin
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Margin updated successfully', data);
        })
        .catch(error => {
            console.error('Error updating margin:', error);
        });
    }
    
    // Send budget values to backend
    function sendBudgetsToBackend(type, budgets) {
        fetch('/api/energy-budget/budgets', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                type: type,
                budgets: budgets,
                month: month,
                year: year,
                startDay: today
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Budgets updated successfully', data);
        })
        .catch(error => {
            console.error('Error updating budgets:', error);
        });
    }
    
    // Show notification message
    function showNotification(message) {
        // You could implement a more sophisticated notification system
        alert(message);
    }
    
    // Show error message
    function showError(message) {
        // You could implement a more sophisticated error display
        alert('Error: ' + message);
    }
</script>