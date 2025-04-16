@props([''])

@php 

$today = (int)date('d');
$month = date('m');
$year = date('Y');

$daysInMonth = (int)(new DateTime())->format('t');

$remainingDaysInMonth = [];

$monthNames = ['Januari','Februari','Maart','April','Mei','Juni','Juli','Augustus','September','Oktober','November','December'];

for ($i = $today; $i <= $daysInMonth; $i++) {
    $remainingDaysInMonth[] = $i;
}

$randomValueForDailyBudget =  rand(20, 50);

@endphp

<div class="p-4">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Dagelijkse budgetten</h2>

    <div class="flex flex-row w-full gap-8">

        <!-- Gas sectie -->
        <div class="w-1/2">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Gas</h2>
            <canvas id="gas-chart"></canvas>
            <div class="flex flex-col items-center justify-center w-20 mb-4">
                <!-- <input type="range" min="0" max="100" value="50" id="gas-margin-slider" class="vertical-slider">
                <p class="mt-4 text-sm text-gray-600"><span id="gas-margin-text">50</span>%</p>
                <p class="text-sm text-gray-600 mb-2">marge</p>
                <button class="py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">opslaan</button> -->
            </div>
        </div>

        <div class="w-0.5 h-auto bg-gray-200 m-2"></div>

        <!-- Electriciteits sectie -->
        <div class="w-1/2">
            <h2 class="text-xl font-bold text-gray-800 mb-2">Elektriciteit</h2>

            <!-- Grafiek sectie -->
            <canvas id="electricity-chart"></canvas>

            <!-- Aanpassen dagbudgetten sectie -->
            <button onclick="toggleBudgetSectieElectriciteit()" class="flex items-center text-left focus:outline-none dark:text-white mb-4">
                <h2 class="text font-semibold text-gray-800 dark:text-white">Dagbudgetten aanpassen</h2>
                <svg id="configSectionIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 transform transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            <div id="budget-sectie-electriciteit" class="hidden">
            <h2 class="text-2xl font-bold text-gray-800 mb-2 flex items-center">
                {{ $monthNames[$month - 1] }}
                
                @if (now()->day !== 1)
                    <span class="ml-2 px-2 py-1 text-xs rounded-md bg-blue-100 text-blue-700 dark:bg-blue-700 dark:text-blue-100">
                        Resterende dagen
                    </span>
                @endif
            </h2>
            <div class="grid grid-cols-7 gap-4 mt-4 mb-6">
                @foreach ($remainingDaysInMonth as $remainingDay)
                    <div class="flex flex-col">
                        <label for="day-{{ $remainingDay }}">{{ $remainingDay }}</label>
                        <input type="number" id="day-{{ $remainingDay }}" name="day-{{ $remainingDay }}" min="20" max="50" value="{{ $randomValueForDailyBudget }}" class="border rounded p-1">
                    </div>
                @endforeach
            </div>
        </div>

           <!-- Aanpassen marge sectie -->
           <button onclick="toggleMargeSectieElectriciteit()" class="flex items-center text-left focus:outline-none dark:text-white mb-4">
                <h2 class="text font-semibold text-gray-800 dark:text-white">Marge aanpassen</h2>
                <svg id="configSectionIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 transform transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            <div id="marge-sectie-electriciteit" class="hidden">
                    <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                        
                        <div class="flex flex-col items-center justify-center w-full md:w-1/4">
                            <input type="range" min="0" max="100" value="50" id="electricity-margin-slider" class="vertical-slider mb-4">
                            <p class="text-sm text-gray-600"><span id="electricity-margin-text">50</span>%</p>
                            <p class="text-sm text-gray-600 mb-4">marge</p>

                            <button class="py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                                Opslaan
                            </button>
                        </div>

                        <div class="space-y-3 md:w-3/4">
                            <p class="text-sm text-gray-700">Voor elektriciteit worden<br>onderstaande marges<br>aangeraden:</p>
                            <div class="flex flex-col gap-2 text-sm">
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                                    <span class="text-gray-700">Hoog (30%+)</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                                    <span class="text-gray-700">Medium (20–30%)</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full bg-green-400"></span>
                                    <span class="text-gray-700">Laag (10–20%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
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
    
    /* Sliders */
    // const gas_margin_slider = document.getElementById('gas-margin-slider');
    const electricity_margin_slider = document.getElementById('electricity-margin-slider');

    // const gas_margin_text = document.getElementById('gas-margin-text');
    const electricity_margin_text = document.getElementById('electricity-margin-text');

    // gas_margin_slider.addEventListener('input', () => {
    //     gas_margin_text.innerHTML = gas_margin_slider.value;
    // });

    electricity_margin_slider.addEventListener('input', () => {
        electricity_margin_text.innerHTML = electricity_margin_slider.value;
    });

    /* Generate labels for all days in current month */
    const today = new Date();
    const currentMonth = today.getMonth(); // 0-indexed
    const year = today.getFullYear();

    function getDaysInMonth(month, year) {
        return new Date(year, month + 1, 0).getDate();
    }

    const daysInMonth = getDaysInMonth(currentMonth, year);
    const monthNames = ["January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"];
    const currentMonthName = monthNames[currentMonth];

    let dateLabels = [];
    for (let day = 1; day <= daysInMonth; day++) {
        dateLabels.push(`${day}-${currentMonth + 1}`);
    }

    /* Sample data to simulate actual usage */
    const generateRandomData = (length) => Array.from({ length }, () => Math.floor(Math.random() * 100));

    const gasData = generateRandomData(daysInMonth);
    const electricityData = generateRandomData(daysInMonth);

    /* Chart Options */
    const baseOptions = (yLabel) => ({
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
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

    /* Gas Chart */
    new Chart(document.getElementById('gas-chart'), {
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
        options: baseOptions('Dagbudgetten (m³)')
    });

    /* Electricity Chart */
    new Chart(document.getElementById('electricity-chart'), {
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
        options: baseOptions('Dagbudgetten (kWh)')
    });

    /* toggle budget sectie electriciteit */
    function toggleBudgetSectieElectriciteit() {
        const budgetSectie = document.getElementById('budget-sectie-electriciteit');
        budgetSectie.classList.toggle('hidden');
    }

    function toggleMargeSectieElectriciteit() {
        const margeSectie = document.getElementById('marge-sectie-electriciteit');
        margeSectie.classList.toggle('hidden');
    }

</script>
