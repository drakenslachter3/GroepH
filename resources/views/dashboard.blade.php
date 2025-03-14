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
            <!-- Datum en periode selectie tools -->
           <!-- Datum en periode selectie tools -->
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6 bg-white border-b border-gray-200">
        <!-- Huidige geselecteerde datum/periode weergave -->
        <div class="mb-4 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">
                @switch($period)
                    @case('day')
                        Energieverbruik op {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
                        @break
                    @case('month')
                        Energieverbruik in {{ \Carbon\Carbon::parse($date)->format('F Y') }}
                        @break
                    @case('year')
                        Energieverbruik in {{ \Carbon\Carbon::parse($date)->format('Y') }}
                        @break
                    @default
                        Energieverbruik
                @endswitch
            </h2>
        </div>

        <div class="flex flex-col md:flex-row md:justify-between md:items-center space-y-4 md:space-y-0">
            <div>
                <h3 class="text-lg font-medium mb-2">Tijdsperiode</h3>
                <div class="flex space-x-4">
                    <a href="{{ route('energy.dashboard', ['period' => 'day', 'date' => $date, 'housing_type' => $housingType]) }}" 
                       class="px-4 py-2 rounded-md {{ ($period ?? 'month') === 'day' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        Dag
                    </a>
                    <a href="{{ route('energy.dashboard', ['period' => 'month', 'date' => $date, 'housing_type' => $housingType]) }}" 
                       class="px-4 py-2 rounded-md {{ ($period ?? 'month') === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        Maand
                    </a>
                    <a href="{{ route('energy.dashboard', ['period' => 'year', 'date' => $date, 'housing_type' => $housingType]) }}" 
                       class="px-4 py-2 rounded-md {{ ($period ?? 'month') === 'year' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        Jaar
                    </a>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-medium mb-2">Datumkiezer</h3>
                <div class="flex items-center space-x-2">
                    @switch($period)
                        @case('day')
                            <input type="date" id="datePicker" class="date-picker" value="{{ $date }}">
                            @break
                        @case('month')
                            <input type="month" id="datePicker" class="date-picker" value="{{ \Carbon\Carbon::parse($date)->format('Y-m') }}">
                            @break
                        @case('year')
                            <input type="number" id="datePicker" class="date-picker" value="{{ \Carbon\Carbon::parse($date)->format('Y') }}" min="2000" max="2050">
                            @break
                    @endswitch
                    <button id="applyDate" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Toepassen</button>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-medium mb-2">Woningtype</h3>
                <select id="housingType" class="px-4 py-2 rounded-md border border-gray-300">
                    <option value="appartement" {{ ($housingType ?? 'tussenwoning') === 'appartement' ? 'selected' : '' }}>Appartement</option>
                    <option value="tussenwoning" {{ ($housingType ?? 'tussenwoning') === 'tussenwoning' ? 'selected' : '' }}>Tussenwoning</option>
                    <option value="hoekwoning" {{ ($housingType ?? 'tussenwoning') === 'hoekwoning' ? 'selected' : '' }}>Hoekwoning</option>
                    <option value="twee_onder_een_kap" {{ ($housingType ?? 'tussenwoning') === 'twee_onder_een_kap' ? 'selected' : '' }}>2-onder-1-kap</option>
                    <option value="vrijstaand" {{ ($housingType ?? 'tussenwoning') === 'vrijstaand' ? 'selected' : '' }}>Vrijstaand</option>
                </select>
            </div>
        </div>
        
        <!-- Navigatiepijlen voor eenvoudige datum navigatie -->
        <div class="flex justify-center mt-6">
            <a href="{{ route('energy.dashboard', ['period' => $period, 'date' => \Carbon\Carbon::parse($date)->sub(1, $period)->format('Y-m-d'), 'housing_type' => $housingType]) }}" 
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-l-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <a href="{{ route('energy.dashboard', ['period' => $period, 'date' => \Carbon\Carbon::now()->format('Y-m-d'), 'housing_type' => $housingType]) }}" 
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 mx-1">
                Vandaag
            </a>
            <a href="{{ route('energy.dashboard', ['period' => $period, 'date' => \Carbon\Carbon::parse($date)->add(1, $period)->format('Y-m-d'), 'housing_type' => $housingType]) }}" 
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-r-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</div>

            <!-- Voorspelling en prognose kaart -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Prognose & Voorspelling</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Elektriciteit Prognose</h4>
                            <div class="relative pt-1">
                                <div class="overflow-hidden h-4 mb-1 text-xs flex rounded bg-blue-200">
                                    <div style="width: {{ min(($totals['electricity_percentage'] ?? 0) * ($period === 'month' ? date('j') / date('t') : 1), 100) }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>0%</span>
                                    <span class="tooltip">
                                        {{ round(($totals['electricity_percentage'] ?? 0) * ($period === 'month' ? date('j') / date('t') : 1)) }}% van budget gebruikt
                                        <span class="tooltiptext">
                                            Gebaseerd op uw huidige verbruik, verwachten we dat u {{ round(($totals['electricity_percentage'] ?? 0)) }}% van uw budget zult gebruiken aan het einde van de periode.
                                        </span>
                                    </span>
                                    <span>100%</span>
                                </div>
                                <p class="mt-2 text-sm">
                                    Verwacht eindverbruik: <strong>{{ number_format(($totals['electricity_kwh'] ?? 0) / ($period === 'month' ? date('j') / date('t') : 1), 2) }} kWh</strong>
                                    <span class="trend-indicator {{ ($period === 'month' && ($totals['electricity_percentage'] ?? 0) > 100) ? 'trend-up' : 'trend-down' }}">
                                        {{ ($period === 'month' && ($totals['electricity_percentage'] ?? 0) > 100) ? '↑' : '↓' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Gas Prognose</h4>
                            <div class="relative pt-1">
                                <div class="overflow-hidden h-4 mb-1 text-xs flex rounded bg-yellow-200">
                                    <div style="width: {{ min(($totals['gas_percentage'] ?? 0) * ($period === 'month' ? date('j') / date('t') : 1), 100) }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-yellow-500"></div>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>0%</span>
                                    <span class="tooltip">
                                        {{ round(($totals['gas_percentage'] ?? 0) * ($period === 'month' ? date('j') / date('t') : 1)) }}% van budget gebruikt
                                        <span class="tooltiptext">
                                            Gebaseerd op uw huidige verbruik, verwachten we dat u {{ round(($totals['gas_percentage'] ?? 0)) }}% van uw budget zult gebruiken aan het einde van de periode.
                                        </span>
                                    </span>
                                    <span>100%</span>
                                </div>
                                <p class="mt-2 text-sm">
                                    Verwacht eindverbruik: <strong>{{ number_format(($totals['gas_m3'] ?? 0) / ($period === 'month' ? date('j') / date('t') : 1), 2) }} m³</strong>
                                    <span class="trend-indicator {{ ($period === 'month' && ($totals['gas_percentage'] ?? 0) > 100) ? 'trend-up' : 'trend-down' }}">
                                        {{ ($period === 'month' && ($totals['gas_percentage'] ?? 0) > 100) ? '↑' : '↓' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budgetstatus cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Elektriciteit Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-semibold">Elektriciteit Status</h3>
                            <div class="tooltip">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="tooltiptext">
                                    Dit toont uw elektriciteitsverbruik ten opzichte van uw budget. Een lager percentage is beter voor het milieu en uw portemonnee.
                                </span>
                            </div>
                        </div>
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
                        
                        <!-- Progress bar met animatie -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                                <div class="h-4 rounded-full transition-all duration-1000 ease-out
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
                        
                        <!-- Historische vergelijking -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <h4 class="font-medium text-gray-700 mb-2">Vergelijking met vorig jaar</h4>
                            <div class="flex items-center">
                                <div class="w-2/3 bg-gray-200 rounded-full h-3">
                                    <div class="h-3 rounded-full bg-blue-400" style="width: 85%"></div>
                                </div>
                                <span class="ml-3 text-sm">-15% vergeleken met vorig jaar</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gas Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-semibold">Gas Status</h3>
                            <div class="tooltip">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="tooltiptext">
                                    Dit toont uw gasverbruik ten opzichte van uw budget. Een lager percentage is beter voor het milieu en uw portemonnee.
                                </span>
                            </div>
                        </div>
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
                        
                        <!-- Progress bar met animatie -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                                <div class="h-4 rounded-full transition-all duration-1000 ease-out
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
                        
                        <!-- Historische vergelijking -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <h4 class="font-medium text-gray-700 mb-2">Vergelijking met vorig jaar</h4>
                            <div class="flex items-center">
                                <div class="w-2/3 bg-gray-200 rounded-full h-3">
                                    <div class="h-3 rounded-full bg-yellow-400" style="width: 92%"></div>
                                </div>
                                <span class="ml-3 text-sm">-8% vergeleken met vorig jaar</span>
                            </div>
                        </div>
                    </div>
                </div>
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

            <!-- Gepersonaliseerde besparingstips -->
            <div id="savingTips" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Gepersonaliseerde Besparingstips</h3>
                    
                    <div class="space-y-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-medium text-blue-700">Elektriciteit Besparing</h4>
                            <p class="mt-1 text-blue-600">Uw verbruik is het hoogst tussen 18:00 en 21:00. Overweeg het gebruik van grote apparaten te verplaatsen naar daluren (21:00-07:00) om ongeveer 14% te besparen op uw elektriciteitskosten.</p>
                        </div>
                        
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <h4 class="font-medium text-yellow-700">Gas Besparing</h4>
                            <p class="mt-1 text-yellow-600">Uw gasverbruik op weekenddagen is 30% hoger dan doordeweeks. Overweeg uw thermostaat programmeerbare instellingen aan te passen om energie te besparen als u thuis bent.</p>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="font-medium text-green-700">Seizoensadvies</h4>
                            <p class="mt-1 text-green-600">Het stookseizoen begint binnenkort. Controleer de isolatie van uw woning en overweeg het gebruik van tochtstrips om tot 8% te besparen op uw gasverbruik.</p>
                        </div>
                    </div>
                </div>
            </div>
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
    
    // Woningtype selector
    document.getElementById('housingType').addEventListener('change', function() {
        window.location.href = "{{ route('energy.dashboard') }}?period={{ $period ?? 'month' }}&housing_type=" + this.value;
    });
    
    // Datum picker functionaliteit
   // Datum picker functionaliteit
document.getElementById('applyDate').addEventListener('click', function() {
    const dateInput = document.getElementById('datePicker').value;
    const period = "{{ $period ?? 'month' }}";
    let formattedDate;
    
    // Formatteer de datum op basis van het type input
    if (period === 'day') {
        formattedDate = dateInput; // Reeds in YYYY-MM-DD formaat
    } else if (period === 'month') {
        // Voor maandselectie (YYYY-MM formaat), zet om naar YYYY-MM-01
        formattedDate = dateInput + '-01';
    } else if (period === 'year') {
        // Voor jaarselectie (YYYY formaat), zet om naar YYYY-01-01
        formattedDate = dateInput + '-01-01';
    }
    
    window.location.href = "{{ route('energy.dashboard') }}?period=" + period + 
                          "&date=" + formattedDate + 
                          "&housing_type={{ $housingType ?? 'tussenwoning' }}";
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
</x-app-layout>