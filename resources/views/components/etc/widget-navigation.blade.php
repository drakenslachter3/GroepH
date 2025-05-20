@props([
    'previousText' => 'Ga naar vorige widget',
    'nextText' => 'Ga naar volgende widget',
    'showPrevious' => false,
    'showNext' => false
])

@if($showPrevious)
<div class="mt-6">
    <button id="skip-button-prev"
            tabindex="0"
            class="skip-button-prev"
            onclick="skipToPreviousWidget()"
            onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); skipToPreviousWidget(); }">
        {{ $previousText }}
    </button>
</div>
@endif

@if($showNext)
<div class="mt-6">
    <button id="skip-button-next"
            tabindex="0"
            class="skip-button-next "
            onclick="skipToNextWidget()"
            onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); skipToNextWidget(); }">
            {{ $nextText }}
    </button>
</div>
@endif

{{-- sr-only focus:not-sr-only focus:p-2 focus:border focus:border-blue-500 focus:rounded-md focus:block --}}