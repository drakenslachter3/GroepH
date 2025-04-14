@props(['type', 'usage', 'target', 'cost', 'percentage', 'status', 'unit'])

<div class="p-4">
    <div class="flex justify-between items-start mb-4">
        <h3 class="text-lg font-semibold dark:text-white">{{ $type }} Status</h3>
        <div class="tooltip relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="tooltiptext invisible group-hover:visible absolute z-10 px-3 py-2 text-sm bg-gray-800 text-white rounded shadow-lg -right-4 -bottom-20 w-48 dark:text-white">
                Dit toont uw {{ strtolower($type) }}verbruik ten opzichte van uw budget. Een lager percentage is beter voor het milieu en uw portemonnee.
            </span>
        </div>
    </div>
    
    <div class="space-y-2">
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-white">Verbruik:</span>
            <span class="font-bold ">{{ number_format($usage, 2) }} {{ $unit }}</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-white">Target:</span>
            <span class="font-bold">{{ number_format($target, 2) }} {{ $unit }}</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-white">Kosten:</span>
            <span class="font-bold">€ {{ number_format($cost, 2) }}</span>
        </div>
    </div>
   
    <!-- Progress bar met animatie en dynamische zwarte streep -->
    <div class="mt-4">
        @php
            // Bereken de positie van de zwarte streep en de kleurzones
            if ($percentage <= 100) {
                // Als onder 100%, dan is de streeppositie gelijk aan het percentage
                $dividerPosition = $percentage;
                $greenZoneWidth = $percentage;
                $redZoneWidth = 0;
            } else {
                // Als boven 100%, dan beweegt de streep naar links, 
                // tot maximaal 75% naar links (dus minimaal 25% positie)
                $overshootPercentage = $percentage - 100; // hoeveel over 100%
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
            }
        @endphp
        
        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden relative">
            <!-- Groene deel (loopt tot aan zwarte streep) -->
            <div 
                class="absolute h-full transition-all duration-1000 ease-out bg-green-500"
                style="width: {{ $greenZoneWidth }}%; left: 0;"
            ></div>
            
            <!-- Rode deel (loopt vanaf zwarte streep tot 100%) -->
            @if($percentage > 100)
                <div 
                    class="absolute h-full transition-all duration-1000 ease-out bg-red-500"
                    style="width: {{ $redZoneWidth }}%; left: {{ $greenZoneWidth }}%;"
                ></div>
            @endif
            
            <!-- Zwarte streep op 100% markering -->
            <div 
                class="absolute top-0 bottom-0 w-1 bg-black z-10 transition-all duration-1000 ease-out"
                style="left: {{ $dividerPosition }}%;"
            ></div>
        </div>
        
        <div class="flex justify-between mt-1 relative">
            <span class="text-sm text-gray-600 dark:text-white">0%</span>
            
            <!-- 100% label onder de zwarte streep -->
            <span class="text-sm text-gray-600 dark:text-white absolute transform -translate-x-1/2"
                  style="left: {{ $dividerPosition }}%;">100%</span>
            
            <span class="text-sm font-medium
                    {{ $status === 'goed' ? 'text-green-700' :
                       ($status === 'waarschuwing' ? 'text-yellow-700' : 'text-red-700') }}">
                {{ number_format($percentage, 1) }}%
            </span>
        </div>
    </div>
   
    <!-- Historische vergelijking -->
    <div class="mt-4 pt-4 border-t border-gray-200">
        <h4 class="font-medium text-gray-700 mb-2 dark:text-white">Vergelijking met vorig jaar</h4>
        <div class="flex items-center">
            <div class="w-2/3 bg-gray-200 rounded-full h-3">
                <div class="h-3 rounded-full {{ $type === 'Elektriciteit' ? 'bg-blue-400' : 'bg-yellow-400' }}" style="width: {{ rand(75, 95) }}%"></div>
            </div>
            <span class="ml-3 text-sm dark:text-white">-{{ rand(5, 25) }}% vergeleken met vorig jaar</span>
        </div>
    </div>
</div>