<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Energieverbruik Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Periode selector -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex space-x-4">
                        <a href="{{ route('energy.dashboard', ['period' => 'day']) }}" 
                           class="px-4 py-2 rounded-md {{ ($period ?? 'month') === 'day' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            Dag
                        </a>
                        <a href="{{ route('energy.dashboard', ['period' => 'month']) }}" 
                           class="px-4 py-2 rounded-md {{ ($period ?? 'month') === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            Maand
                        </a>
                        <a href="{{ route('energy.dashboard', ['period' => 'year']) }}" 
                           class="px-4 py-2 rounded-md {{ ($period ?? 'month') === 'year' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            Jaar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Budgetstatus cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Elektriciteit Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">Elektriciteit Status</h3>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-700">Verbruik:</span>
                            <span class="font-bold">{{ number_format($totals['electricity_kwh'] ?? 0, 2) }} kWh</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-700">Target:</span>
                            <span class="font-bold">{{ number_format($totals['electricity_target'] ?? 0, 2) }} kWh</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-700">Kosten:</span>
                            <span class="font-bold">€ {{ number_format($totals['electricity_euro'] ?? 0, 2) }}</span>
                        </div>
                        
                        <!-- Progress bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div class="h-4 rounded-full 
                                        {{ ($totals['electricity_status'] ?? '') === 'goed' ? 'bg-green-500' : 
                                           (($totals['electricity_status'] ?? '') === 'waarschuwing' ? 'bg-yellow-500' : 'bg-red-500') }}"
                                     style="width: {{ min(($totals['electricity_percentage'] ?? 0), 100) }}%">
                                </div>
                            </div>
                            <div class="flex justify-between mt-1">
                                <span class="text-sm text-gray-600">0%</span>
                                <span class="text-sm font-medium 
                                        {{ ($totals['electricity_status'] ?? '') === 'goed' ? 'text-green-700' : 
                                           (($totals['electricity_status'] ?? '') === 'waarschuwing' ? 'text-yellow-700' : 'text-red-700') }}">
                                    {{ number_format($totals['electricity_percentage'] ?? 0, 1) }}%
                                </span>
                                <span class="text-sm text-gray-600">100%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gas Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">Gas Status</h3>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-700">Verbruik:</span>
                            <span class="font-bold">{{ number_format($totals['gas_m3'] ?? 0, 2) }} m³</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-700">Target:</span>
                            <span class="font-bold">{{ number_format($totals['gas_target'] ?? 0, 2) }} m³</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-700">Kosten:</span>
                            <span class="font-bold">€ {{ number_format($totals['gas_euro'] ?? 0, 2) }}</span>
                        </div>
                        
                        <!-- Progress bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div class="h-4 rounded-full 
                                        {{ ($totals['gas_status'] ?? '') === 'goed' ? 'bg-green-500' : 
                                           (($totals['gas_status'] ?? '') === 'waarschuwing' ? 'bg-yellow-500' : 'bg-red-500') }}"
                                     style="width: {{ min(($totals['gas_percentage'] ?? 0), 100) }}%">
                                </div>
                            </div>
                            <div class="flex justify-between mt-1">
                                <span class="text-sm text-gray-600">0%</span>
                                <span class="text-sm font-medium 
                                        {{ ($totals['gas_status'] ?? '') === 'goed' ? 'text-green-700' : 
                                           (($totals['gas_status'] ?? '') === 'waarschuwing' ? 'text-yellow-700' : 'text-red-700') }}">
                                    {{ number_format($totals['gas_percentage'] ?? 0, 1) }}%
                                </span>
                                <span class="text-sm text-gray-600">100%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafiek Elektriciteit -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Elektriciteitsverbruik (kWh)</h3>
                    <div style="height: 300px;">
                        <canvas id="electricityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Grafiek Gas -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Gasverbruik (m³)</h3>
                    <div style="height: 300px;">
                        <canvas id="gasChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Grafiek Kosten -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Kosten (€)</h3>
                    <div style="height: 300px;">
                        <canvas id="costChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Besparingstips -->
            @if(($totals['electricity_status'] ?? '') === 'kritiek' || ($totals['gas_status'] ?? '') === 'kritiek')
                <div class="bg-red-50 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 border-b border-red-200">
                        <h3 class="text-lg font-semibold text-red-700 mb-4">Besparingstips</h3>
                        <ul class="list-disc pl-5 text-red-700">
                            <li class="mb-1">Verlaag uw thermostaat met 1-2 graden om tot 6% energie te besparen.</li>
                            <li class="mb-1">Controleer of er apparaten zijn die onnodig aanstaan of in stand-by modus zijn.</li>
                            <li class="mb-1">Gebruik grote apparaten (wasmachine, droger) tijdens daluren.</li>
                            <li class="mb-1">Vervang oude gloeilampen door energiezuinige LED verlichting.</li>
                            <li class="mb-1">Zet apparaten helemaal uit in plaats van op stand-by.</li>
                        </ul>
                    </div>
                </div>
            @elseif(($totals['electricity_status'] ?? '') === 'waarschuwing' || ($totals['gas_status'] ?? '') === 'waarschuwing')
                <div class="bg-yellow-50 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 border-b border-yellow-200">
                        <h3 class="text-lg font-semibold text-yellow-700 mb-4">Besparingstips</h3>
                        <ul class="list-disc pl-5 text-yellow-700">
                            <li class="mb-1">Let op uw verbruik tijdens piekuren.</li>
                            <li class="mb-1">Gebruik energiezuinige LED-verlichting.</li>
                            <li class="mb-1">Overweeg het verminderen van de verwarming in ongebruikte kamers.</li>
                        </ul>
                    </div>
                </div>
            @else
                <div class="bg-green-50 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 border-b border-green-200">
                        <h3 class="text-lg font-semibold text-green-700 mb-4">U doet het goed!</h3>
                        <p class="text-green-700">U blijft goed binnen uw budget! Blijf zo doorgaan.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Charts.js scripts -->
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
        
        // Format voor labels op basis van periode
        const periodLabels = {
            'day': 'Uur',
            'month': 'Dag',
            'year': 'Maand'
        };
        
        // Elektriciteit Chart
        const electricityCtx = document.getElementById('electricityChart').getContext('2d');
        new Chart(electricityCtx, {
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
                }
            }
        });
        
        // Gas Chart
        const gasCtx = document.getElementById('gasChart').getContext('2d');
        new Chart(gasCtx, {
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
                }
            }
        });
        
        // Kosten Chart
        const costCtx = document.getElementById('costChart').getContext('2d');
        new Chart(costCtx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Elektriciteit (€)',
                        data: chartData.cost.electricity,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: 'Gas (€)',
                        data: chartData.cost.gas,
                        backgroundColor: 'rgba(245, 158, 11, 0.5)',
                        borderColor: 'rgb(245, 158, 11)',
                        borderWidth: 1
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
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Kosten (€)'
                        }
                    }
                }
            }
        });
    </script>
</x-app-layout>