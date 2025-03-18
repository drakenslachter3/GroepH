@props(['electricityData', 'gasData', 'period'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6 bg-white border-b border-gray-200">
        <h3 class="text-lg font-semibold mb-4">Verbruiksvoorspelling</h3>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Electricity Prediction -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex justify-between items-start">
                    <h4 class="font-medium text-blue-800">Elektriciteit</h4>
                    <span class="text-sm bg-blue-200 text-blue-800 px-2 py-1 rounded">{{ ucfirst($period) }}</span>
                </div>
                
                @php
                    $electricityValue = $electricityData['kwh'] ?? 0;
                    $electricityPercentage = $electricityData['percentage'] ?? 0;
                    $electricityForecast = 0;
                    $electricityTrend = 0;
                    
                    // Calculate forecast based on current usage and period
                    switch($period) {
                        case 'day':
                            $electricityForecast = round($electricityValue * 1.05, 2);
                            $electricityTrend = rand(-5, 15); // Sample trend
                            break;
                        case 'month':
                            $electricityForecast = round($electricityValue * 1.08, 2);
                            $electricityTrend = rand(-8, 12); // Sample trend
                            break;
                        case 'year':
                            $electricityForecast = round($electricityValue * 1.03, 2);
                            $electricityTrend = rand(-3, 8); // Sample trend
                            break;
                    }
                @endphp
                
                <div class="mt-3 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-blue-700">Huidig verbruik:</span>
                        <span class="font-bold text-blue-900">{{ number_format($electricityValue, 2) }} kWh</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-blue-700">Voorspelling rest {{ $period }}:</span>
                        <span class="font-bold text-blue-900">{{ number_format($electricityForecast, 2) }} kWh</span>
                    </div>
                    
                    <div class="h-2 bg-blue-200 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full" style="width: {{ min($electricityPercentage, 100) }}%"></div>
                    </div>
                    
                    <div class="flex items-center {{ $electricityTrend > 0 ? 'text-red-600' : 'text-green-600' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if($electricityTrend > 0)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            @endif
                        </svg>
                        <span>{{ abs($electricityTrend) }}% {{ $electricityTrend > 0 ? 'stijging' : 'daling' }} t.o.v. vorige {{ $period }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Gas Prediction -->
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="flex justify-between items-start">
                    <h4 class="font-medium text-yellow-800">Gas</h4>
                    <span class="text-sm bg-yellow-200 text-yellow-800 px-2 py-1 rounded">{{ ucfirst($period) }}</span>
                </div>
                
                @php
                    $gasValue = $gasData['m3'] ?? 0;
                    $gasPercentage = $gasData['percentage'] ?? 0;
                    $gasForecast = 0;
                    $gasTrend = 0;
                    
                    // Calculate forecast based on current usage and period
                    switch($period) {
                        case 'day':
                            $gasForecast = round($gasValue * 1.03, 2);
                            $gasTrend = rand(-8, 10); // Sample trend
                            break;
                        case 'month':
                            $gasForecast = round($gasValue * 1.12, 2);
                            // Larger variation in winter months
                            $month = date('n');
                            $gasTrend = ($month >= 10 || $month <= 3) ? rand(-5, 20) : rand(-15, 5);
                            break;
                        case 'year':
                            $gasForecast = round($gasValue * 1.04, 2);
                            $gasTrend = rand(-5, 12); // Sample trend
                            break;
                    }
                @endphp
                
                <div class="mt-3 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-yellow-700">Huidig verbruik:</span>
                        <span class="font-bold text-yellow-900">{{ number_format($gasValue, 2) }} m³</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-yellow-700">Voorspelling rest {{ $period }}:</span>
                        <span class="font-bold text-yellow-900">{{ number_format($gasForecast, 2) }} m³</span>
                    </div>
                    
                    <div class="h-2 bg-yellow-200 rounded-full overflow-hidden">
                        <div class="h-full bg-yellow-600 rounded-full" style="width: {{ min($gasPercentage, 100) }}%"></div>
                    </div>
                    
                    <div class="flex items-center {{ $gasTrend > 0 ? 'text-red-600' : 'text-green-600' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if($gasTrend > 0)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            @endif
                        </svg>
                        <span>{{ abs($gasTrend) }}% {{ $gasTrend > 0 ? 'stijging' : 'daling' }} t.o.v. vorige {{ $period }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recommendation Section -->
        <div class="mt-6 p-4 bg-green-50 rounded-lg">
            <h4 class="font-medium text-green-800 mb-2">Besparingsadvies</h4>
            @switch($period)
                @case('day')
                    <p class="text-green-700">
                        @if(date('H') < 12)
                            Plan energieverbruikende activiteiten tijdens de dag wanneer er mogelijk zonne-energie beschikbaar is.
                        @elseif(date('H') < 18)
                            Uw verbruik ligt op schema. Denk eraan om apparaten volledig uit te schakelen na gebruik.
                        @else
                            Probeer grote apparaten zoals wasmachines en vaatwassers te gebruiken tijdens daluren om kosten te besparen.
                        @endif
                    </p>
                    @break
                @case('month')
                    <p class="text-green-700">
                        @php $dayOfMonth = date('j'); @endphp
                        @if($dayOfMonth < 10)
                            Uw verbruik aan het begin van de maand bepaalt grotendeels uw eindrekening. Focus nu op effectief energiegebruik.
                        @elseif($dayOfMonth < 20)
                            U zit halverwege de maand. Vergelijk uw verbruik met uw budget en pas aan indien nodig.
                        @else
                            De maand loopt ten einde. Uw huidige verbruikstrend geeft aan dat u 
                            {{ rand(90, 110) }}% van uw maandelijkse budget zult gebruiken.
                        @endif
                    </p>
                    @break
                @default
                    <p class="text-green-700">
                        @php $month = date('n'); @endphp
                        @if($month >= 4 && $month <= 9)
                            In de warmere maanden kunt u besparen op verwarmingskosten. Focus op het verminderen van onnodig elektriciteitsverbruik voor koeling.
                        @else
                            In het stookseizoen kunt u tot 15% besparen door slimme thermostaten en goede isolatie. Houd deuren en ramen gesloten en verlaag de temperatuur 's nachts.
                        @endif
                    </p>
            @endswitch
        </div>
    </div>
</div>