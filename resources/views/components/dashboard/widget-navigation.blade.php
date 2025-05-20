@props([
    'previousText' => 'Ga naar vorige widget',
    'nextText' => 'Ga naar volgende widget',
    'showPrevious' => false,
    'showNext' => false
])

@if($showPrevious)
    <button tabindex="0"
            class="skip-button-prev sr-only focus:not-sr-only"
            onclick="skipToPreviousWidget()"
            onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); skipToPreviousWidget(); }">
        {{ $previousText }}
    </button>
@endif

@if($showNext)
    <button tabindex="0"
            class="skip-button-next sr-only focus:not-sr-only"
            onclick="skipToNextWidget()"
            onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); skipToNextWidget(); }">
            {{ $nextText }}
    </button>
@endif
