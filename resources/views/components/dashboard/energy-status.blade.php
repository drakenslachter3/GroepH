@props([
    'type',
    'title',
    'usage',
    'target',
    'percentage',
    'status',
    'unit',
    'date' => null,
    'period' => null,
    'liveData' => null,
])

@php
    // Use live data if available, otherwise fall back to provided data
    $actualUsage = $liveData['usage'] ?? ($usage ?? 0);
    $actualTarget = $liveData['target'] ?? ($target ?? 0);
    $actualPercentage = $liveData['percentage'] ?? ($percentage ?? 0);
    $actualStatus = $liveData['status'] ?? ($status ?? 'goed');
    $actualCost = $liveData['cost'] ?? 0;

    // Enhanced status calculation with better thresholds
    if ($actualPercentage > 100) {
        $actualStatus = 'kritiek';
    } elseif ($actualPercentage > 85) {
        $actualStatus = 'waarschuwing';
    } else {
        $actualStatus = 'goed';
    }
    $difference = $actualUsage - $actualTarget;
        $differenceText = $difference > 0 
            ? 'boven target met ' . number_format($difference, 2) . " $unit" 
            : ($difference < 0 
                ? 'onder target met ' . number_format(abs($difference), 2) . " $unit" 
                : 'precies op target');
@endphp

<section class="p-2" aria-labelledby="usage-widget-title">
    <div class="flex justify-between items-start mb-4">
        <x-widget-navigation :showPrevious="true" />
        <x-widget-heading :title="$title" :type="$type" :date="$date" :period="$period" />
        <x-widget-navigation :showNext="true" />
        
        <!-- Info tooltip -->
        <div class="tooltip relative">
            <button id="info-tooltip-button" 
                    tabindex="0" 
                    aria-label="Meer informatie over {{ strtolower($type) }} status" 
                    aria-expanded="false"
                    aria-controls="tooltip-content"
                    class="focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full p-1"
                    onclick="toggleTooltip()"
                    onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); toggleTooltip(); }">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 cursor-pointer hover:text-gray-600 dark:text-gray-300 dark:hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
            <div id="tooltip-content" role="tooltip" class="tooltiptext invisible absolute z-10 px-3 py-2 text-sm bg-gray-800 text-white rounded shadow-lg -right-4 -bottom-20 w-48" aria-hidden="true">
                Dit toont uw {{ strtolower($type) }}verbruik ten opzichte van uw budget. Een lager percentage is beter voor het milieu.
            </div>
        </div>
    </div>

    <!-- Third tab stop: Complete summary that can be focused - IMPROVED READABLE FORMAT -->
    <div id="usage-summary" tabindex="0" class="sr-only focus:not-sr-only focus:p-2 focus:border focus:border-blue-500 focus:rounded-md">
        Uw {{ strtolower($type) }}verbruik is <strong>{{ $actualStatus }}</strong>. U heeft {{ number_format($actualUsage, 2) }} {{ $unit }} verbruikt, met een target van {{ number_format($actualTarget, 2) }} {{ $unit }}. 
        Dat is {{ $differenceText }} ({{ number_format($actualPercentage, 1) }}% van target).
    </div>

    <!-- Individual metrics, each can be focused with IMPROVED READABILITY -->
    <div class="space-y-2">
        <!-- Usage value -->
        <div class="flex justify-between items-center">
            <span tabindex="0" class="text-gray-700 dark:text-gray-300">Verbruik:</span>
            <span tabindex="0" class="font-bold dark:text-white">{{ number_format($actualUsage, 2) }} {{ $unit }}</span>
        </div>
        
        <!-- Target value -->
        <div class="flex justify-between items-center">
            <span tabindex="0" class="text-gray-700 dark:text-gray-300">Target:</span>
            <span tabindex="0" class="font-bold dark:text-white">{{ number_format($actualTarget, 2) }} {{ $unit }}</span>
        </div>
    </div>
   
    <!-- Progress bar section -->
    <div class="mt-4">
        @php
            if ($actualPercentage <= 100) {
                $dividerPosition = $actualPercentage;
                $greenZoneWidth = $actualPercentage;
                $redZoneWidth = 0;
                $streepLabel = number_format($actualPercentage, 1) . '%'; // Label onder de streep
                $rightLabel = '100%'; // Label rechts
            } else {
                $overshootPercentage = $actualPercentage - 100; // hoeveel over 100%
                $maxShift = 75; // maximale verschuiving in procenten (naar links)
                $shift = min($maxShift, ($overshootPercentage / 100) * $maxShift);
                $dividerPosition = max(25, 100 - $shift);
                $greenZoneWidth = $dividerPosition;
                $redZoneWidth = 100 - $dividerPosition;
                $streepLabel = '100%'; // Label onder de streep
                $rightLabel = number_format($actualPercentage, 1) . '%'; // Label rechts
            }
        @endphp

        <!-- Seventh tab stop: Focused progress bar with full description -->
        <div tabindex="0" 
             aria-label="Voortgangsbalk voor {{ strtolower($type) }} verbruik"
             aria-valuemin="0" 
             aria-valuemax="100" 
             aria-valuenow="{{ min($actualPercentage, 100) }}"
             aria-valuetext="@if($actualPercentage <= 100) U heeft {{ number_format($actualPercentage, 1) }}% van uw target verbruikt. U zit {{ number_format(100 - $actualPercentage, 1) }}% onder uw target. @else U heeft {{ number_format($actualPercentage, 1) }}% van uw target verbruikt. U zit {{ number_format($actualPercentage - 100, 1) }}% boven uw target. @endif"
             class="relative mt-6">
            
            <!-- Progress bar visualization (hidden from screen readers) -->
            <div class="flex justify-between mt-1 relative" aria-hidden="true">
                <span class="text-xs text-gray-600 dark:text-gray-400">0%</span>
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
            
            <!-- Visual Progress bar -->
            <div class="w-full h-4 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden relative">
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
                
                <!-- Behoud ook de originele overflow indicator voor >100% -->
                @if($actualPercentage > 100)
                    @php
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

            <!-- IMPROVED: Clear status message with target comparison -->
            <div class="flex justify-between mt-1 relative" aria-hidden="true">
                <span class="text-xs text-gray-600 dark:text-gray-400 absolute transform -translate-x-1/2"
                    style="left: {{ $dividerPosition }}%;">{{ $streepLabel }}</span>
            </div>
        </div>
        
        <!-- Eighth tab stop: Status message with IMPROVED target comparison -->
        <div tabindex="0" class="mt-4 text-xs">
            @if($actualPercentage < 80)
                <span class="text-green-600 dark:text-green-400">Uitstekend! Je verbruik ligt {{ number_format(100 - $actualPercentage, 1) }}% onder je target.</span>
            @elseif($actualPercentage < 95)
                <span class="text-green-600 dark:text-green-400">Goed! Je blijft {{ number_format(100 - $actualPercentage, 1) }}% onder je target.</span>
            @elseif($actualPercentage < 100)
                <span class="text-yellow-600 dark:text-yellow-400">Let op: Je nadert je target. Nog {{ number_format(100 - $actualPercentage, 1) }}% te gaan.</span>
            @elseif($actualPercentage < 110)
                <span class="text-orange-600 dark:text-orange-400">Waarschuwing: Je overschrijdt je target met {{ number_format($actualPercentage - 100, 1) }}%.</span>
            @else
                <span class="text-red-600 dark:text-red-400">Alert: Je overschrijdt je target aanzienlijk met {{ number_format($actualPercentage - 100, 1) }}%!</span>
            @endif
        </div>
    </div>
   
    <!-- Historical comparison section -->
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <h4 id="comparison-heading" class="font-medium text-gray-700 mb-2 dark:text-gray-300" tabindex="0">Vergelijking met vorig jaar</h4>
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                @php
                    // Use real data from InfluxDB instead of random values
                    $reductionPercent = $liveData['previous_year']['reduction_percent'] ?? 0;
                    $icon = $reductionPercent > 0 ? 'trending-down' : 'trending-up';
                    $color =
                        $reductionPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                    $previousYearValue = $liveData['previous_year']['usage'] ?? 0;
                    if (is_array($previousYearValue)) {
                        $previousYearValue = array_sum(array_filter($previousYearValue, 'is_numeric'));
                    }
                @endphp
                
                <!-- Ninth tab stop: Comparison text -->
                <div class="flex items-center" tabindex="0" aria-labelledby="comparison-heading">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $color }} mr-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        @if($reductionPercent > 0)
                            <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd" />
                        @else
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                        @endif
                    </svg>
                    <span class="{{ $color }} text-xs">{{ abs($reductionPercent) }}% {{ $reductionPercent > 0 ? 'vermindering' : 'toename' }} vergeleken met vorig jaar</span>
                </div>
            </div>
            
            <!-- Tenth tab stop: Previous year value -->
            <span class="text-xs text-gray-500 dark:text-gray-400" tabindex="0">{{ number_format($previousYearValue, 2) }} {{ $unit }}</span>
        </div>
    </div>
</section>

<script>
// Add JavaScript functionality for keyboard interaction
function toggleTooltip() {
    const tooltip = document.getElementById('tooltip-content');
    const button = document.getElementById('info-tooltip-button');
    
    if (tooltip.classList.contains('invisible')) {
        // Show tooltip
        tooltip.classList.remove('invisible');
        tooltip.setAttribute('aria-hidden', 'false');
        button.setAttribute('aria-expanded', 'true');
        
        // Announce to screen readers
        const announcement = document.createElement('div');
        announcement.setAttribute('role', 'status');
        announcement.setAttribute('aria-live', 'polite');
        announcement.textContent = tooltip.textContent;
        document.body.appendChild(announcement);
        setTimeout(() => document.body.removeChild(announcement), 3000);
    } else {
        // Hide tooltip
        tooltip.classList.add('invisible');
        tooltip.setAttribute('aria-hidden', 'true');
        button.setAttribute('aria-expanded', 'false');
    }
    
    // Close tooltip when focus is lost
    document.addEventListener('focusin', function(e) {
        if (e.target !== button && !tooltip.contains(e.target)) {
            tooltip.classList.add('invisible');
            tooltip.setAttribute('aria-hidden', 'true');
            button.setAttribute('aria-expanded', 'false');
        }
    }, { once: true });
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') skipToNextWidget();
    if (e.key === 'ArrowLeft') skipToPreviousWidget();
  });

// Add keyboard listener for Escape key to close tooltips
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const tooltip = document.getElementById('tooltip-content');
        const button = document.getElementById('info-tooltip-button');
        
        if (!tooltip.classList.contains('invisible')) {
            tooltip.classList.add('invisible');
            tooltip.setAttribute('aria-hidden', 'true');
            button.setAttribute('aria-expanded', 'false');
            button.focus();
        }
    }
});

// Make Enter key work on the progress bar to announce the status
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const activeElement = document.activeElement;
        if (activeElement && activeElement.getAttribute('aria-valuetext')) {
            const announcement = document.createElement('div');
            announcement.setAttribute('role', 'status');
            announcement.setAttribute('aria-live', 'assertive');
            announcement.textContent = activeElement.getAttribute('aria-valuetext');
            document.body.appendChild(announcement);
            setTimeout(() => document.body.removeChild(announcement), 3000);
        }
    }
});
</script>
