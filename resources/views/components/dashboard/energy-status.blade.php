@props(['type', 'usage', 'target', 'cost', 'percentage', 'status', 'unit', 'date' => null, 'period' => null])

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
                Dit toont uw {{ strtolower($type) }}verbruik ten opzichte van uw budget. Een lager percentage is beter voor het milieu en uw portemonnee.
            </span>
        </div>
    </div>
    
    <div class="space-y-2">
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Verbruik:</span>
            <span class="font-bold dark:text-white">{{ number_format($usage, 2) }} {{ $unit }}</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Target:</span>
            <span class="font-bold dark:text-white">{{ number_format($target, 2) }} {{ $unit }}</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Kosten:</span>
            <span class="font-bold dark:text-white">€ {{ number_format($cost, 2) }}</span>
        </div>
    </div>
   
    <!-- Verbeterde progressbar met kleurverloop en beperkte overflow indicator -->
    <div class="mt-4">
        <!-- Container voor progressbar met duidelijke begrenzing -->
        <div class="w-full h-4 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden relative">
            @php
            // Bereken het percentage voor de weergave, maximaal 100% voor de primaire balk
            $displayWidth = min($percentage, 100);
            
            // Bereken de kleur gebaseerd op het percentage
            // Van groen (0%) naar geel (50%) naar rood (100%)
            if ($percentage <= 50) {
                // Groen naar geel (0-50%)
                $red = round(($percentage / 50) * 255);
                $green = 255;
                $blue = 0;
            } else {
                // Geel naar rood (50-100%)
                $red = 255;
                $green = round(255 - (($percentage - 50) / 50) * 255);
                $blue = 0;
            }
            $barColor = "rgb($red, $green, $blue)";
            @endphp
            
            <!-- Primaire balk (0-100%) -->
            <div class="h-full rounded-lg transition-all duration-1000 ease-out"
                 style="width: {{ $displayWidth }}%; background-color: {{ $barColor }};">
            </div>
            
            <!-- Overflow indicator (voor waarden >100%, maar maximaal 20% extra) -->
            @if($percentage > 100)
                @php
                    // Beperk de overflow tot maximaal 20% extra
                    $overflowWidth = min($percentage - 100, 20);
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
        
        <!-- Labels voor de balk -->
        <div class="flex justify-between mt-1">
            <span class="text-xs text-gray-600 dark:text-gray-400">0%</span>
            <span class="text-xs font-medium" style="color: {{ $percentage > 100 ? '#DC2626' : $barColor }}">
                {{ number_format($percentage, 1) }}%
                @if($percentage > 100)
                <span class="ml-1 inline-block">!</span>
                @endif
            </span>
            <span class="text-xs text-gray-600 dark:text-gray-400">100%</span>
        </div>
        
        <!-- Status bericht -->
        <div class="mt-2 text-xs">
            @if($percentage < 80)
                <span class="text-green-600 dark:text-green-400">Uitstekend! Je verbruik ligt ruim onder je target.</span>
            @elseif($percentage < 95)
                <span class="text-green-600 dark:text-green-400">Goed! Je blijft onder je target.</span>
            @elseif($percentage < 100)
                <span class="text-yellow-600 dark:text-yellow-400">Let op: Je nadert je target.</span>
            @elseif($percentage < 110)
                <span class="text-orange-600 dark:text-orange-400">Waarschuwing: Je overschrijdt je target.</span>
            @else
                <span class="text-red-600 dark:text-red-400">Alert: Je overschrijdt je target aanzienlijk!</span>
            @endif
        </div>
        
        <!-- Periode aanduiding -->
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            @if(isset($period))
                @switch($period)
                    @case('day')
                        Dagelijks verbruik
                        @break
                    @case('month')
                        Maandelijks verbruik
                        @break
                    @case('year')
                        Jaarlijks verbruik
                        @break
                    @default
                        Verbruik
                @endswitch
            @else
                Verbruik
            @endif
        </div>
    </div>
   
    <!-- Historische vergelijking -->
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <h4 class="font-medium text-gray-700 mb-2 dark:text-gray-300">Vergelijking met vorig jaar</h4>
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                @php
                    $reductionPercent = rand(5, 25);
                    $icon = $reductionPercent > 0 ? 'trending-down' : 'trending-up';
                    $color = $reductionPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                @endphp
                
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $color }} mr-1" viewBox="0 0 20 20" fill="currentColor">
                    @if($reductionPercent > 0)
                        <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd" />
                    @else
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                    @endif
                </svg>
                <span class="{{ $color }} text-xs">{{ abs($reductionPercent) }}% vergeleken met vorig jaar</span>
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($usage * (1 + $reductionPercent/100), 2) }} {{ $unit }}</span>
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