<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Energieverbruik Dashboard') }}
        </h2>
    </x-slot>

    <!-- Custom CSS voor tooltips en verbeterde interactiviteit -->
    <style>
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 10px;
            position: absolute;
            z-index: 100;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        .trend-indicator {
            font-size: 1.2em;
            font-weight: bold;
            margin-left: 5px;
        }
        
        .trend-up {
            color: #EF4444;
        }
        
        .trend-down {
            color: #10B981;
        }
        
        .trend-stable {
            color: #6B7280;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background-color: #EF4444;
            color: white;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }
        
        .comparison-card {
            background-color: #F9FAFB;
            transition: all 0.3s ease;
        }
        
        .comparison-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .date-picker {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #D1D5DB;
            background-color: white;
        }
    </style>

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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg comparison-card">
                    <div class="p-4 border-b border-gray-200">
                        <h4 class="font-medium text-gray-800">Afgelopen Week</h4>
                        <div class="flex justify-between mt-2">
                            <div>
                                <p class="text-sm text-gray-600">Elektriciteit</p>
                                <p class="font-bold">42.8 kWh</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Gas</p>
                                <p class="font-bold">12.3 m³</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg comparison-card">
                    <div class="p-4 border-b border-gray-200">
                        <h4 class="font-medium text-gray-800">Afgelopen Maand</h4>
                        <div class="flex justify-between mt-2">
                            <div>
                                <p class="text-sm text-gray-600">Elektriciteit</p>
                                <p class="font-bold">180.5 kWh</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Gas</p>
                                <p class="font-bold">52.7 m³</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg comparison-card">
                    <div class="p-4 border-b border-gray-200">
                        <h4 class="font-medium text-gray-800">Zelfde Periode Vorig Jaar</h4>
                        <div class="flex justify-between mt-2">
                            <div>
                                <p class="text-sm text-gray-600">Elektriciteit</p>
                                <p class="font-bold">210.3 kWh</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Gas</p>
                                <p class="font-bold">57.1 m³</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafiek Elektriciteit met interactieve tooltips -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold">Elektriciteitsverbruik (kWh)</h3>
                        <div>
                            <button id="toggleElectricityComparison" class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                Toon Vorig Jaar
                            </button>
                        </div>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="electricityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Grafiek Gas met interactieve tooltips -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold">Gasverbruik (m³)</h3>
                        <div>
                            <button id="toggleGasComparison" class="text-sm px-3 py-1 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200">
                                Toon Vorig Jaar
                            </button>
                        </div>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="gasChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Trendanalyse -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Trendanalyse</h3>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Langetermijnverbruik (Elektriciteit)</h4>
                            <div style="height: 250px;">
                                <canvas id="electricityTrendChart"></canvas>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Langetermijnverbruik (Gas)</h4>
                            <div style="height: 250px;">
                                <canvas id="gasTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gepersonaliseerde besparingstips component -->
            <x-saving-tips :tips="$savingTips ?? []" />
        </div>
    </div>
    
    <!-- Waarschuwingsnotificatie -->
    <div id="budgetWarning" class="notification">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>U zit op 85% van uw maandelijkse energiebudget!</span>
        </div>
        <button id="closeNotification" class="ml-2 text-white hover:text-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

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
        
        // Elektriciteit Chart met interactieve tooltips
        const electricityCtx = document.getElementById('electricityChart').getContext('2d');
        const electricityChart = new Chart(electricityCtx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'kWh Verbruik',
                        data: chartData.electricity.data,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: 'Target',
                        data: chartData.electricity.target,
                        type: 'line',
                        borderColor: 'rgb(220, 38, 38)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: periodLabels['{{ $period ?? 'month' }}']
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Elektriciteit (kWh)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const dataIndex = context[0].dataIndex;
                                const value = chartData.electricity.data[dataIndex];
                                const target = chartData.electricity.target[dataIndex];
                                const percentage = target ? (value / target * 100).toFixed(1) : 0;
                                return `${percentage}% van je target\nKosten: €${(value * {{ $conversionService->electricityRate ?? 0.35 }}).toFixed(2)}`;
                            }
                        }
                    }
                }
            }
        });
        
        // Gas Chart met interactieve tooltips
        const gasCtx = document.getElementById('gasChart').getContext('2d');
        const gasChart = new Chart(gasCtx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'm³ Verbruik',
                        data: chartData.gas.data,
                        backgroundColor: 'rgba(245, 158, 11, 0.5)',
                        borderColor: 'rgb(245, 158, 11)',
                        borderWidth: 1
                    },
                    {
                        label: 'Target',
                        data: chartData.gas.target,
                        type: 'line',
                        borderColor: 'rgb(220, 38, 38)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: periodLabels['{{ $period ?? 'month' }}']
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Gas (m³)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const dataIndex = context[0].dataIndex;
                                const value = chartData.gas.data[dataIndex];
                                const target = chartData.gas.target[dataIndex];
                                const percentage = target ? (value / target * 100).toFixed(1) : 0;
                                return `${percentage}% van je target\nKosten: €${(value * {{ $conversionService->gasRate ?? 1.45 }}).toFixed(2)}`;
                            }
                        }
                    }
                }
            }
        });
        
        // Trend Charts voor lange termijn analyse
        const electricityTrendCtx = document.getElementById('electricityTrendChart').getContext('2d');
        new Chart(electricityTrendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Dit Jaar',
                        data: [210, 195, 180, 170, 165, 168, 172, 175, 168, 182, 190, 200],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Vorig Jaar',
                        data: [230, 220, 200, 185, 180, 182, 190, 195, 185, 200, 210, 225],
                        borderColor: 'rgb(107, 114, 128)',
                        borderDash: [5, 5],
                        backgroundColor: 'rgba(107, 114, 128, 0)',
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        title: {
                            display: true,
                            text: 'kWh per maand'
                        }
                    }
                }
            }
        });
        
        const gasTrendCtx = document.getElementById('gasTrendChart').getContext('2d');
        new Chart(gasTrendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Dit Jaar',
                        data: [120, 115, 90, 65, 40, 25, 20, 20, 35, 70, 100, 110],
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Vorig Jaar',
                        data: [130, 125, 100, 70, 45, 30, 25, 25, 40, 75, 110, 120],
                        borderColor: 'rgb(107, 114, 128)',
                        borderDash: [5, 5],
                        backgroundColor: 'rgba(107, 114, 128, 0)',
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        title: {
                            display: true,
                            text: 'm³ per maand'
                        }
                    }
                }
            }
        });
        
        // Toggle vergelijking met vorig jaar
        document.getElementById('toggleElectricityComparison').addEventListener('click', function() {
            const button = this;
            const dataset = electricityChart.data.datasets.find(ds => ds.label === 'Vorig Jaar');
            
            if (dataset) {
                // Verwijder de dataset als deze al bestaat
                electricityChart.data.datasets = electricityChart.data.datasets.filter(ds => ds.label !== 'Vorig Jaar');
                button.textContent = 'Toon Vorig Jaar';
                button.classList.remove('bg-blue-200');
                button.classList.add('bg-blue-100');
            } else {
                // Voeg de dataset toe
                electricityChart.data.datasets.push({
                    label: 'Vorig Jaar',
                    data: lastYearData.electricity,
                    backgroundColor: 'rgba(107, 114, 128, 0.5)',
                    borderColor: 'rgb(107, 114, 128)',
                    borderWidth: 1
                });
                button.textContent = 'Verberg Vorig Jaar';
                button.classList.remove('bg-blue-100');
                button.classList.add('bg-blue-200');
            }
            
            electricityChart.update();
        });
        
        document.getElementById('toggleGasComparison').addEventListener('click', function() {
            const button = this;
            const dataset = gasChart.data.datasets.find(ds => ds.label === 'Vorig Jaar');
            
            if (dataset) {
                // Verwijder de dataset als deze al bestaat
                gasChart.data.datasets = gasChart.data.datasets.filter(ds => ds.label !== 'Vorig Jaar');
                button.textContent = 'Toon Vorig Jaar';
                button.classList.remove('bg-yellow-200');
                button.classList.add('bg-yellow-100');
            } else {
                // Voeg de dataset toe
                gasChart.data.datasets.push({
                    label: 'Vorig Jaar',
                    data: lastYearData.gas,
                    backgroundColor: 'rgba(107, 114, 128, 0.5)',
                    borderColor: 'rgb(107, 114, 128)',
                    borderWidth: 1
                });
                button.textContent = 'Verberg Vorig Jaar';
                button.classList.remove('bg-yellow-100');
                button.classList.add('bg-yellow-200');
            }
            
            gasChart.update();
        });
        
        // Budget waarschuwing notificatie
        @if(($totals['electricity_percentage'] ?? 0) > 80 || ($totals['gas_percentage'] ?? 0) > 80)
            setTimeout(() => {
                document.getElementById('budgetWarning').style.display = 'flex';
            }, 2000);
        @endif
        
        document.getElementById('closeNotification').addEventListener('click', function() {
            document.getElementById('budgetWarning').style.display = 'none';
        });
    </script>
@stack('scripts')
</x-app-layout>