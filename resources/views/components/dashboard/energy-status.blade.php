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
    $actualUsage = $liveData['usage'];
    $actualTarget = $liveData['target'] ?? ($target ?? 0);
    $actualPercentage = $liveData['percentage'] ?? ($percentage ?? 0);
    $actualStatus = $liveData['status'] ?? ($status ?? 'goed');
    $actualCost = $liveData['cost'] ?? 0;

    // Status calculation
    if ($actualPercentage > 100) {
        $actualStatus = 'kritiek';
    } elseif ($actualPercentage > 85) {
        $actualStatus = 'waarschuwing';
    } else {
        $actualStatus = 'goed';
    }
    
    $difference = $actualUsage - $actualTarget;
    $differenceText = $difference > 0 
        ? __('energy-status.above_target', ['amount' => number_format($difference, 2, ',', '.'), 'unit' => $unit])
        : ($difference < 0 
            ? __('energy-status.below_target', ['amount' => number_format(abs($difference), 2, ',', '.'), 'unit' => $unit])
            : __('energy-status.on_target'));
@endphp

<section class="p-2" aria-labelledby="usage-widget-title">
    <div class="flex justify-between mb-4">
        <x-dashboard.widget-navigation :showPrevious="true" />
        <x-dashboard.widget-heading :title="$title . ' (' . $unit . ')'" :type="$type" :date="$date" :period="$period" />
        <x-dashboard.widget-navigation :showNext="true" />
        
        <!-- Info tooltip -->
        <div class="tooltip relative">
            <button id="info-tooltip-button" 
                    tabindex="0" 
                    aria-label="{{ __('energy-status.more_info', ['type' => strtolower($type)]) }}" 
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
                {{ __('energy-status.tooltip_text', ['type' => strtolower($type)]) }}
            </div>
        </div>
    </div>

    <!-- Metrics -->
    <div class="space-y-2">
        <!-- Usage value -->
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">{{ __('energy-status.usage') }}:</span>
            <span tabindex="0"
                aria-label="{{ __('energy-status.usage_aria', [
                    'date' => \Carbon\Carbon::parse($date)->translatedFormat('d F'),
                    'usage' => number_format($actualUsage, 2, ',', '.'),
                    'unit' => $unit,
                    'difference' => $differenceText,
                    'percentage' => number_format($actualPercentage, 1, ',', '.')
                ]) }}"
                class="font-bold dark:text-white">
                {{ number_format($actualUsage, 2, ',', '.') }} {{ $unit }}
            </span>
        </div>
        
        <!-- Target value -->
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">{{ __('energy-status.target') }}:</span>
            <span tabindex="0"
                aria-label="{{ __('energy-status.target_aria', [
                    'date' => \Carbon\Carbon::parse($date)->translatedFormat('d F'),
                    'target' => number_format($actualTarget, 2, ',', '.'),
                    'unit' => $unit
                ]) }}"
                class="font-bold dark:text-white">
                {{ number_format($actualTarget, 2, ',', '.') }} {{ $unit }}
            </span>
        </div>
    </div>
   
    <!-- Progress bar section -->
    <div class="mt-4">
        @php
            if ($actualPercentage <= 100) {
                $dividerPosition = $actualPercentage;
                $greenZoneWidth = $actualPercentage;
                $redZoneWidth = 0;
                $streepLabel = number_format($actualPercentage, 1, ',', '.') . '%';
                $rightLabel = '100%';
            } else {
                $overshootPercentage = $actualPercentage - 100;
                $maxShift = 75;
                $shift = min($maxShift, ($overshootPercentage / 100) * $maxShift);
                $dividerPosition = max(25, 100 - $shift);
                $greenZoneWidth = $dividerPosition;
                $redZoneWidth = 100 - $dividerPosition;
                $streepLabel = '100%';
                $rightLabel = number_format($actualPercentage, 1, ',', '.') . '%';
            }
        @endphp

        <div
             aria-label="{{ __('energy-status.progress_aria', ['type' => strtolower($type)]) }}"
             aria-valuemin="0" 
             aria-valuemax="100" 
             aria-valuenow="{{ min($actualPercentage, 100) }}"
             aria-valuetext="@if($actualPercentage <= 100) {{ __('energy-status.progress_under', ['percentage' => number_format($actualPercentage, 1, ',', '.'), 'remaining' => number_format(100 - $actualPercentage, 1, ',', '.')]) }} @else {{ __('energy-status.progress_over', ['percentage' => number_format($actualPercentage, 1, ',', '.'), 'excess' => number_format($actualPercentage - 100, 1, ',', '.')]) }} @endif"
             class="relative mt-6">
            
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
            
            <!-- Progress bar visual -->
            <div class="w-full h-4 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden relative">
                <!-- Green zone -->
                <div 
                    class="absolute h-full transition-all duration-1000 ease-out bg-green-500"
                    style="width: {{ $greenZoneWidth }}%; left: 0;"
                ></div>
                
                <!-- Red zone -->
                @if($actualPercentage > 100)
                    <div 
                        class="absolute h-full transition-all duration-1000 ease-out bg-red-600"
                        style="width: {{ $redZoneWidth }}%; left: {{ $greenZoneWidth }}%;"
                    ></div>
                @endif
                
                <!-- Overflow indicator -->
                @if($actualPercentage > 100)
                    @php
                        $overflowWidth = min($actualPercentage - 100, 20);
                    @endphp
                    <div class="absolute top-0 right-0 h-full bg-red-600 border-l border-white dark:border-gray-800"
                        style="width: {{ $overflowWidth }}%; transform: translateX(100%);">
                    </div>
                    
                    <!-- Arrow for overflow -->
                    <div class="absolute top-0 right-0 h-full flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                @endif
            </div>

            <!-- Progress labels -->
            <div class="flex justify-between mt-1 relative" aria-hidden="true">
                <span class="text-xs text-gray-600 dark:text-gray-400 absolute transform -translate-x-1/2"
                    style="left: {{ $dividerPosition }}%;">{{ $streepLabel }}</span>
            </div>
        </div>
        
        <!-- Status message -->
        <div tabindex="0" class="mt-4 text-xs">
            @if($actualPercentage < 80)
                <span class="text-green-600 dark:text-green-400">{{ __('energy-status.excellent', ['percentage' => number_format(100 - $actualPercentage, 1, ',', '.')]) }}</span>
            @elseif($actualPercentage < 95)
                <span class="text-green-600 dark:text-green-400">{{ __('energy-status.good', ['percentage' => number_format(100 - $actualPercentage, 1, ',', '.')]) }}</span>
            @elseif($actualPercentage < 100)
                <span class="text-yellow-600 dark:text-yellow-400">{{ __('energy-status.warning', ['percentage' => number_format(100 - $actualPercentage, 1, ',', '.')]) }}</span>
            @else
                <span class="text-orange-600 dark:text-orange-400">{{ __('energy-status.alert', ['percentage' => number_format($actualPercentage - 100, 1, ',', '.')]) }}</span>
            @endif
        </div>
    </div>
   
    <!-- Historical comparison --> 
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <h4 id="comparison-heading" class="font-medium text-gray-700 mb-2 dark:text-gray-300" tabindex="0">
            {{ __('energy-status.comparison_title') }}
        </h4>

        <div class="flex items-center justify-between" aria-labelledby="comparison-heading">
            @php
                $reductionPercent = $liveData['previous_year']['reduction_percent'] ?? 0;
                $icon = $reductionPercent > 0 ? 'trending-down' : 'trending-up';
                $color = $reductionPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                $previousYearValue = $liveData['previous_year']['usage'] ?? 0;
                if (is_array($previousYearValue)) {
                    $previousYearValue = array_sum(array_filter($previousYearValue, 'is_numeric'));
                }
                $differenceType = $reductionPercent > 0 ? __('energy-status.reduction') : __('energy-status.increase');
            @endphp
            
            <!-- Comparison block -->
            <div class="flex items-center text-xs" tabindex="0" 
                aria-label="{{ __('energy-status.comparison_aria', [
                    'percentage' => number_format(abs($reductionPercent), 1, ',', '.'),
                    'type' => $differenceType,
                    'previous' => number_format($previousYearValue, 2, ',', '.'),
                    'unit' => $unit
                ]) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $color }} mr-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    @if($reductionPercent > 0)
                        <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd" />
                    @else
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                    @endif
                </svg>

                <!-- Percentage change -->
                <span class="{{ $color }} mr-2">
                    {{ number_format(abs($reductionPercent), 1, ',', '.') }}% {{ $differenceType }}
                </span>

                <!-- Previous year value -->
                <span class="text-gray-500 dark:text-gray-400">
                    ({{ number_format($previousYearValue, 2, ',', '.') }} {{ $unit }})
                </span>
            </div>
        </div>
    </div>

</section>

<script>
// Tooltip functionality
function toggleTooltip() {
    const tooltip = document.getElementById('tooltip-content');
    const button = document.getElementById('info-tooltip-button');
    
    if (tooltip.classList.contains('invisible')) {
        tooltip.classList.remove('invisible');
        tooltip.setAttribute('aria-hidden', 'false');
        button.setAttribute('aria-expanded', 'true');
        
        // Screen reader announcement
        const announcement = document.createElement('div');
        announcement.setAttribute('role', 'status');
        announcement.setAttribute('aria-live', 'polite');
        announcement.textContent = tooltip.textContent;
        document.body.appendChild(announcement);
        setTimeout(() => document.body.removeChild(announcement), 3000);
    } else {
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

// Navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') skipToNextWidget();
    if (e.key === 'ArrowLeft') skipToPreviousWidget();
});

// Close tooltip with Escape
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

// Progress bar Enter key functionality
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