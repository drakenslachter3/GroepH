@props(['type', 'usage', 'target', 'percentage', 'status', 'unit', 'date' => null, 'period' => null])

<section class="p-2">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h3 class="text-lg font-semibold dark:text-white">{{ $type }} Status</h3>
            
            <!-- Date display -->
            <div class="mt-1 inline-block bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-md text-xs font-medium text-gray-700 dark:text-gray-300" aria-live="polite">
                <?php
                    switch ($period) {
                        case 'day':
                            echo date('d M Y', strtotime($date));
                            break;
                        case 'month':
                            echo date('M Y', strtotime($date));
                            break;
                        case 'year':
                            echo date('Y', strtotime($date));
                            break;
                    }
                ?>
            </div>
        </div>
        
        <div class="tooltip relative">
            <button aria-label="Meer informatie over {{ strtolower($type) }} status" class="focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 cursor-pointer hover:text-gray-600 dark:text-gray-300 dark:hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
            <span class="tooltiptext invisible absolute z-10 px-3 py-2 text-sm bg-gray-800 text-white rounded shadow-lg -right-4 -bottom-20 w-48" role="tooltip">
                Dit toont uw {{ strtolower($type) }}verbruik ten opzichte van uw budget. Een lager percentage is beter voor het milieu.
            </span>
        </div>
    </div>
    
    <!-- Usage summary - first tab stop -->
    <div class="space-y-2" tabindex="0">
        <p class="sr-only">{{ $type }} samenvatting: {{ $status === 'goed' ? 'Goed' : ($status === 'waarschuwing' ? 'Waarschuwing' : 'Alert') }}. {{ number_format($percentage, 1) }}% van uw target is verbruikt.</p>
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Verbruik:</span>
            <span class="font-bold dark:text-white">{{ number_format($usage, 2) }} {{ $unit }}</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-gray-700 dark:text-gray-300">Target:</span>
            <span class="font-bold dark:text-white">{{ number_format($target, 2) }} {{ $unit }}</span>
        </div>
    </div>

    <div class="mt-4">
        <?php
        if ($percentage <= 100) {
            $dividerPosition = $percentage;
            $greenZoneWidth = $percentage;
            $redZoneWidth = 0;
            $streepLabel = number_format($percentage, 1) . '%';
            $rightLabel = '100%';
        } else {
            $overshootPercentage = $percentage - 100;
            $dividerPosition = 100 - $overshootPercentage;
            $dividerPosition = max(0, $dividerPosition);
            $greenZoneWidth = $dividerPosition;
            $redZoneWidth = 100 - $dividerPosition;
            $streepLabel = '100%';
            $rightLabel = number_format($percentage, 1) . '%';
        }
        ?>
        <div class="relative" aria-hidden="true">
            <!-- Labels above the bar -->
            <div class="flex justify-between mb-1">
                <span class="text-xs text-gray-600 dark:text-gray-400">0%</span>

                <?php
                $labelColorClass = $status === 'goed' ? 'text-green-700 dark:text-green-400'
                    : ($status === 'waarschuwing' ? 'text-yellow-700 dark:text-yellow-400'
                    : 'text-red-700 dark:text-red-400');
                ?>
                <span class="text-xs font-medium <?= $labelColorClass ?>"><?= $rightLabel ?></span>
            </div>

            <!-- Progress bar -->
            <div class="w-full h-4 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden relative">
                <!-- Green part -->
                <div 
                    class="absolute h-full transition-all duration-1000 ease-out bg-green-500"
                    style="width: <?= $greenZoneWidth ?>%; left: 0;"
                ></div>

                <!-- Red part -->
                <?php if ($percentage > 100): ?>
                    <div 
                        class="absolute h-full transition-all duration-1000 ease-out bg-red-600"
                        style="width: <?= $redZoneWidth ?>%; left: <?= $greenZoneWidth ?>%;"
                    ></div>
                <?php endif; ?>

                <!-- Overflow indicator -->
                <?php if ($percentage > 100): ?>
                    <?php $overflowWidth = min($percentage - 100, 20); ?>
                    <div class="absolute top-0 right-0 h-full bg-red-600 border-l border-white dark:border-gray-800"
                        style="width: <?= $overflowWidth ?>%; transform: translateX(100%);">
                    </div>
                    <div class="absolute top-0 right-0 h-full flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Label under the black stripe -->
            <div class="relative">
                <span class="text-xs text-gray-600 dark:text-gray-400 absolute transform -translate-x-1/2 mt-1"
                    style="left: <?= $dividerPosition ?>%;"><?= $streepLabel ?></span>
            </div>
        </div>

        <!-- Status message - second tab stop -->
        <div class="mt-6 text-xs" tabindex="0" aria-live="polite">
            <?php if ($percentage < 80): ?>
                <span class="text-green-600 dark:text-green-400">Uitstekend! Je verbruik ligt ruim onder je target.</span>
            <?php elseif ($percentage < 95): ?>
                <span class="text-green-600 dark:text-green-400">Goed! Je blijft onder je target.</span>
            <?php elseif ($percentage < 100): ?>
                <span class="text-yellow-600 dark:text-yellow-400">Let op: Je nadert je target.</span>
            <?php elseif ($percentage < 110): ?>
                <span class="text-orange-600 dark:text-orange-400">Waarschuwing: Je overschrijdt je target.</span>
            <?php else: ?>
                <span class="text-red-600 dark:text-red-400">Alert: Je overschrijdt je target aanzienlijk!</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historical comparison - third tab stop -->
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700" tabindex="0">
        <h4 id="compare-heading" class="font-medium text-gray-700 mb-2 dark:text-gray-300">Vergelijking met vorig jaar</h4>
        <div class="flex items-center justify-between" aria-labelledby="compare-heading">
            <div class="flex items-center">
                @php
                    $reductionPercent = rand(5, 25);
                    $icon = $reductionPercent > 0 ? 'trending-down' : 'trending-up';
                    $color = $reductionPercent > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                    $compareText = $reductionPercent > 0 
                        ? "Uw verbruik is {$reductionPercent}% lager dan vorig jaar."
                        : "Uw verbruik is " . abs($reductionPercent) . "% hoger dan vorig jaar.";
                @endphp
                
                <span class="sr-only">{{ $compareText }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $color }} mr-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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
</section>

<script>
    // Add keyboard accessibility for tooltip
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipButtons = document.querySelectorAll('.tooltip button');
        
        tooltipButtons.forEach(function(btn) {
            btn.addEventListener('focus', function() {
                const tooltip = this.closest('.tooltip').querySelector('.tooltiptext');
                tooltip.classList.remove('invisible');
            });
            
            btn.addEventListener('blur', function() {
                const tooltip = this.closest('.tooltip').querySelector('.tooltiptext');
                tooltip.classList.add('invisible');
            });
            
            btn.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.blur();
                }
            });
            
            // Show on hover as well
            btn.addEventListener('mouseenter', function() {
                const tooltip = this.closest('.tooltip').querySelector('.tooltiptext');
                tooltip.classList.remove('invisible');
            });
            
            btn.addEventListener('mouseleave', function() {
                const tooltip = this.closest('.tooltip').querySelector('.tooltiptext');
                tooltip.classList.add('invisible');
            });
        });
    });
</script>