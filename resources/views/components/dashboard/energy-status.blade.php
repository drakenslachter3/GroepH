@props(['type', 'usage', 'target', 'percentage', 'status', 'unit', 'date' => null, 'period' => null, 'liveData' => null])

@php
// Use live data if available, otherwise fall back to provided data
$actualUsage = $liveData['usage'] ?? $usage ?? 0;
$actualTarget = $liveData['target'] ?? $target ?? 0;
$actualPercentage = $liveData['percentage'] ?? $percentage ?? 0;
$actualStatus = $liveData['status'] ?? $status ?? 'goed';
$actualCost = $liveData['cost'] ?? 0;

// Enhanced status calculation with better thresholds
if ($actualPercentage > 100) {
    $actualStatus = 'kritiek';
} elseif ($actualPercentage > 85) {
    $actualStatus = 'waarschuwing';
} else {
    $actualStatus = 'goed';
}
@endphp

<div class="p-4">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h3 class="text-lg font-semibold dark:text-white">{{ $type }} Status</h3>
            
            <!-- Datum weergave -->
            @if(isset($date) && isset($period))
                <div class="mt-1 inline-block bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-md text-xs font-medium text-gray-700 dark:text-gray-300">
                    @switch($period)
                        @case('day')
                            {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
                            @break
                        @case('month')
                            {{ \Carbon\Carbon::parse($date)->format('F Y') }}
                            @break
                        @case('year')
                            {{ \Carbon\Carbon::parse($date)->format('Y') }}
                            @break
                        @default
                            {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
                    @endswitch
                </div>
            @endif
        </div>
        
        <div class="tooltip relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 cursor-pointer hover:text-gray-600 dark:text-gray-300 dark:hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="tooltiptext invisible absolute z-10 px-3 py-2 text-sm bg-gray-800 text-white rounded shadow-lg -right-4 -bottom-20 w-48">
                Dit toont uw {{ strtolower($type) }}verbruik ten opzichte van uw budget. Een lager percentage is beter voor het milieu.
            </span>
        </div>
    </div>
    
    <div class="space-y-2">
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Verbruik:</span>
            <span class="font-bold dark:text-white">{{ number_format($actualUsage, 2) }} {{ $unit }}</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Target:</span>
            <span class="font-bold dark:text-white">{{ number_format($actualTarget, 2) }} {{ $unit }}</span>
        </div>
        @if($actualCost > 0)
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Kosten:</span>
            <span class="font-bold dark:text-white">€{{ number_format($actualCost, 2) }}</span>
        </div>
        @endif
    </div>
   
    <!-- Progressbar met dynamische zwarte streep -->
    <div class="mt-4">
        <!-- Container voor progressbar met duidelijke begrenzing -->
        <div class="w-full h-4 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden relative">
            @php
            // Bereken de positie van de zwarte streep en de kleurzones
            if ($actualPercentage <= 100) {
                // Als onder 100%, dan staat de streep op het huidige percentage
                $dividerPosition = $actualPercentage;
                $greenZoneWidth = $actualPercentage;
                $redZoneWidth = 0;
                
                // Labels voor onder/boven 100%
                $streepLabel = number_format($actualPercentage, 1) . '%'; // Label onder de streep
                $rightLabel = '100%'; // Label rechts
            } else {
                // Als boven 100%, dan beweegt de streep naar links, 
                // tot maximaal 75% naar links (dus minimaal 25% positie)
                $overshootPercentage = $actualPercentage - 100; // hoeveel over 100%
                $maxShift = 75; // maximale verschuiving in procenten (naar links)
                
                // Bereken verschuiving op basis van overschrijding (meer overschrijding = meer verschuiving)
                // Bij 0% overschrijding = 0% verschuiving
                // Bij zeer grote overschrijding = maximale verschuiving van 75%
                $shift = min($maxShift, ($overshootPercentage / 100) * $maxShift);
                
                // Bereken uiteindelijke positie (100% - verschuiving)
                // Minimum positie is 25% (bij 75% verschuiving)
                $dividerPosition = max(25, 100 - $shift);
                
                // De groene zone loopt tot aan de streep
                $greenZoneWidth = $dividerPosition;
                
                // De rode zone loopt vanaf de streep tot 100%
                $redZoneWidth = 100 - $dividerPosition;
                
                // Labels voor onder/boven 100%
                $streepLabel = '100%'; // Label onder de streep
                $rightLabel = number_format($actualPercentage, 1) . '%'; // Label rechts
            }
            
            // Bepaal of er een risico is op overlap van labels
            $labelOverlapRisk = $actualPercentage > 100 && $dividerPosition > 70;
            @endphp
            
            <!-- Groene deel (loopt tot aan zwarte streep) -->
            <div 
                class="absolute h-full transition-all duration-1000 ease-out bg-green-500"
                style="width: {{ $greenZoneWidth }}%; left: 0;"
            ></div>
            
            <!-- Rode deel (loopt vanaf zwarte streep tot 100%) -->
            @if($actualPercentage > 100)
                <div 
                    class="absolute h-full transition-all duration-1000 ease-out bg-red-600"
                    style="width: {{ $redZoneWidth }}%; left: {{ $greenZoneWidth }}%;"
                ></div>
            @endif
            
            <!-- Zwarte streep op positie -->
            <div 
                class="absolute top-0 bottom-0 w-1 bg-black z-10 transition-all duration-1000 ease-out"
                style="left: {{ $dividerPosition }}%;"
            ></div>
            
            <!-- Behoud ook de originele overflow indicator voor >100% -->
            @if($actualPercentage > 100)
                @php
                    // Beperk de overflow tot maximaal 20% extra
                    $overflowWidth = min($actualPercentage - 100, 20);
                @endphp
                <div class="absolute top-0 right-0 h-full bg-red-600 border-l border-white dark:border-gray-800"
                     style="width: {{ $overflowWidth }}%; transform: translateX(100%);">
                </div>
                
                <!-- Pijlpunt voor overflow indicator -->
                <div class="absolute top-0 right-0 h-full flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            @endif
        </div>
        
        <!-- Labels voor de balk met verbeterde positionering -->
        <div class="flex justify-between mt-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400">0%</span>
            
            <!-- Label onder de zwarte streep (percentage of 100%) -->
            <span class="text-xs text-gray-600 dark:text-gray-400 absolute transform -translate-x-1/2"
                  style="left: {{ $dividerPosition }}%;">{{ $streepLabel }}</span>
            
            <!-- Rechter label (percentage of 100%) met aangepaste positie -->
            @if($actualPercentage > 100)
                <span class="text-xs font-medium absolute 
                        {{ $actualStatus === 'goed' ? 'text-green-700 dark:text-green-400' :
                           ($actualStatus === 'waarschuwing' ? 'text-yellow-700 dark:text-yellow-400' : 'text-red-700 dark:text-red-400') }}"
                      style="right: -5%; transform: translateX(0);">
                    {{ $rightLabel }}
                    <span class="ml-1 inline-block">!</span>
                </span>
            @else
                <span class="text-xs font-medium
                        {{ $actualStatus === 'goed' ? 'text-green-700 dark:text-green-400' :
                           ($actualStatus === 'waarschuwing' ? 'text-yellow-700 dark:text-yellow-400' : 'text-red-700 dark:text-red-400') }}">
                    {{ $rightLabel }}
                </span>
            @endif
        </div>
        
        <!-- Status bericht -->
        <div class="mt-2 text-xs">
            @if($actualPercentage < 80)
                <span class="text-green-600 dark:text-green-400">Uitstekend! Je verbruik ligt ruim onder je target.</span>
            @elseif($actualPercentage < 95)
                <span class="text-green-600 dark:text-green-400">Goed! Je blijft onder je target.</span>
            @elseif($actualPercentage < 100)
                <span class="text-yellow-600 dark:text-yellow-400">Let op: Je nadert je target.</span>
            @elseif($actualPercentage < 110)
                <span class="text-orange-600 dark:text-orange-400">Waarschuwing: Je overschrijdt je target.</span>
            @else
                <span class="text-red-600 dark:text-red-400">Alert: Je overschrijdt je target aanzienlijk!</span>
            @endif
        </div>
      
    </div>
   
    <!-- Historische vergelijking -->
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <h4 class="font-medium text-gray-700 mb-2 dark:text-gray-300">Vergelijking met vorig jaar</h4>
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                @php
                    // Calculate comparison with previous year based on actual InfluxDB data
                    // This would ideally come from the InfluxDB service
                    $reductionPercent = rand(-15, 25); // Simulated for now - should come from actual data
                    $icon = $reductionPercent > 0 ? 'trending-down' : 'trending-up';
                    $color = $reductionPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                    $previousYearValue = $actualUsage * (1 + $reductionPercent/100);
                @endphp
                
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $color }} mr-1" viewBox="0 0 20 20" fill="currentColor">
                    @if($reductionPercent > 0)
                        <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd" />
                    @else
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                    @endif
                </svg>
                <span class="{{ $color }} text-xs">{{ abs($reductionPercent) }}% {{ $reductionPercent > 0 ? 'vermindering' : 'toename' }} vergeleken met vorig jaar</span>
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($previousYearValue, 2) }} {{ $unit }}</span>
        </div>
    </div>
</div>

<style>
.tooltip .tooltiptext {
    visibility: hidden;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.tooltip:hover .tooltiptext {
    visibility: visible;
    opacity: 1;
}
</style>