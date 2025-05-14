<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Energieverbruik Voorspellingen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Period and Type Selector -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
                        <!-- Period selection -->
                        <div>
                            <h3 class="text-lg font-medium mb-2 dark:text-white">Tijdsperiode</h3>
                            <div class="flex space-x-4">
                                <a href="{{ route('energy.predictions', ['period' => 'day', 'type' => $type, 'date' => $date]) }}" 
                                   class="px-4 py-2 rounded-md {{ $period === 'day' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}">
                                    Dag
                                </a>
                                <a href="{{ route('energy.predictions', ['period' => 'month', 'type' => $type, 'date' => $date]) }}" 
                                   class="px-4 py-2 rounded-md {{ $period === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}">
                                    Maand
                                </a>
                                <a href="{{ route('energy.predictions', ['period' => 'year', 'type' => $type, 'date' => $date]) }}" 
                                   class="px-4 py-2 rounded-md {{ $period === 'year' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}">
                                    Jaar
                                </a>
                            </div>
                        </div>
                        
                        <!-- Energy type selection -->
                        <div>
                            <h3 class="text-lg font-medium mb-2 dark:text-white">Energietype</h3>
                            <div class="flex space-x-4">
                                <a href="{{ route('energy.predictions', ['period' => $period, 'type' => 'electricity', 'date' => $date]) }}" 
                                   class="px-4 py-2 rounded-md {{ $type === 'electricity' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-800/50' }}">
                                    Elektriciteit
                                </a>
                                <a href="{{ route('energy.predictions', ['period' => $period, 'type' => 'gas', 'date' => $date]) }}" 
                                   class="px-4 py-2 rounded-md {{ $type === 'gas' ? 'bg-yellow-600 text-white' : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:hover:bg-yellow-800/50' }}">
                                    Gas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Prediction Chart -->
            <x-dashboard.energy-prediction-chart
                :currentData="$usageData"
                :budgetData="$budgetData"
                :type="$type"
                :period="$period"
                :percentage="$percentage"
                :confidence="$confidence"
            />

            <!-- Detailed Information -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-4 dark:text-white">Voorspellingsdetails</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2 dark:text-gray-300">Actueel verbruik</h4>
                            
                            <p class="dark:text-white">
                                Tot nu toe heb je <span class="font-semibold">{{ number_format(array_sum(array_filter($usageData['actual'], function($value) { return $value !== null; })), 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}</span> 
                                verbruikt, wat neerkomt op <span class="font-semibold">{{ number_format($percentage, 1) }}%</span> van je jaarbudget.
                            </p>
                            <p class="mt-2 dark:text-white">
                                Je jaarlijkse budget is <span class="font-semibold">{{ number_format($budgetData['target'], 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}</span>.
                            </p>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                @if($period === 'year')
                                Als het huidige verbruikspatroon zich voortzet, zal je jaarlijks verbruik ongeveer 
                                <span class="font-semibold text-{{ $percentage > 100 ? 'red' : 'green' }}-600 dark:text-{{ $percentage > 100 ? 'red' : 'green' }}-400">
                                    {{ number_format($usageData['expected'], 2) }} {{ $type === 'electricity' ? 'kWh' : 'm³' }}
                                </span> zijn.
                                @else
                                Deze voorspelling is gebaseerd op je historische verbruikspatronen en seizoensinvloeden.
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2 dark:text-gray-300">Voorspellingsvariatie</h4>
                            <p class="dark:text-white">
                                De voorspelling heeft een marge van <span class="font-semibold">{{ $usageData['margin'] }}%</span>.
                                Dit betekent dat je werkelijke verbruik waarschijnlijk tussen 
                                <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($usageData['best_case'], 2) }}</span> en 
                                <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($usageData['worst_case'], 2) }}</span> 
                                {{ $type === 'electricity' ? 'kWh' : 'm³' }} zal liggen.
                            </p>
                            <p class="mt-2 dark:text-white">
                                De betrouwbaarheid van deze voorspelling is 
                                <span class="font-semibold {{ $confidence > 80 ? 'text-green-600 dark:text-green-400' : ($confidence > 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                    {{ $confidence }}%
                                </span>.
                            </p>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                De betrouwbaarheid is gebaseerd op de hoeveelheid en kwaliteit van de beschikbare gegevens, 
                                seizoenspatronen en consistentie van je verbruik.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Energy Budget Tips -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-4 dark:text-white">Energiebesparingstips</h3>
                    
                    <div class="bg-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-50 p-4 rounded-lg dark:bg-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-900/30">
                        <h4 class="font-medium text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-700 dark:text-{{ $type === 'electricity' ? 'blue' : 'yellow' }}-300 mb-2">
                            {{ $type === 'electricity' ? 'Elektriciteit' : 'Gas' }} besparingsmogelijkheden
                        </h4>
                        
                        @if($type === 'electricity')
                            <ul class="list-disc pl-5 space-y-2 text-gray-700 dark:text-gray-300">
                                <li>Vervang conventionele verlichting door LED-lampen om tot 80% te besparen op verlichtingskosten.</li>
                                <li>Schakel apparaten volledig uit in plaats van stand-by te laten staan, dit kan jaarlijks tot €70 besparen.</li>
                                <li>Gebruik een slimme thermostaat om je verwarming efficiënter te regelen, wat tot 15% kan besparen.</li>
                                <li>Overweeg energiezuinige apparaten (A+++ label) bij vervanging van oude toestellen.</li>
                            </ul>
                        @else
                            <ul class="list-disc pl-5 space-y-2 text-gray-700 dark:text-gray-300">
                                <li>Verlaag je thermostaat met 1°C kan tot 6% besparen op je gasverbruik.</li>
                                <li>Isoleer je woning goed, met name dak, vloer en muren voor maximale besparing.</li>
                                <li>Plaats radiatorfolie achter je radiatoren om warmteverlies via buitenmuren te verminderen.</li>
                                <li>Overweeg een hybride of volledige warmtepomp bij vervanging van je CV-ketel.</li>
                            </ul>
                        @endif
                    </div>
                    
                    <div class="mt-4 bg-{{ $percentage > 100 ? 'red' : 'green' }}-50 p-4 rounded-lg dark:bg-{{ $percentage > 100 ? 'red' : 'green' }}-900/30">
                        <h4 class="font-medium text-{{ $percentage > 100 ? 'red' : 'green' }}-700 dark:text-{{ $percentage > 100 ? 'red' : 'green' }}-300 mb-2">
                            {{ $percentage > 100 ? 'Actie nodig om budget te halen' : 'Je bent op schema om binnen budget te blijven' }}
                        </h4>
                        
                        @if($percentage > 100)
                            <p class="text-gray-700 dark:text-gray-300">
                                Je verbruikt momenteel meer dan gepland. Overweeg de volgende acties:
                            </p>
                            <ul class="list-disc pl-5 mt-2 space-y-1 text-gray-700 dark:text-gray-300">
                                <li>Identificeer de grootste verbruikers in je huishouden en focus eerst op deze apparaten/systemen.</li>
                                <li>Houd een energiedagboek bij om inzicht te krijgen in je verbruikspatronen.</li>
                                <li>Overweeg je energiebudget aan te passen als er veranderingen zijn in je huishouden of levensstijl.</li>
                            </ul>
                        @else
                            <p class="text-gray-700 dark:text-gray-300">
                                Je bent goed op weg om binnen je budget te blijven. Enkele tips om dit vol te houden:
                            </p>
                            <ul class="list-disc pl-5 mt-2 space-y-1 text-gray-700 dark:text-gray-300">
                                <li>Blijf regelmatig je verbruik monitoren om trends en patronen te herkennen.</li>
                                <li>Vier kleine successen en blijf consistent met energiebesparende gewoonten.</li>
                                <li>Overweeg in de toekomst je budget aan te scherpen voor nog meer besparing.</li>
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Load Chart.js for the prediction chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('prediction-chart-scripts')
</x-app-layout>