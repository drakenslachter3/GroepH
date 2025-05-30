@props(['electricityPercentage', 'gasPercentage', 'threshold', 'showWarning'])

<!-- Waarschuwingsnotificatie -->
<div id="budgetWarning" class="notification {{ $showWarning ? 'flex' : 'hidden' }}">
    <div class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>
            @if($electricityPercentage > $threshold && $gasPercentage > $threshold)
                U zit op {{ round(max($electricityPercentage, $gasPercentage)) }}% van uw maandelijkse energiebudget!
            @elseif($electricityPercentage > $threshold)
                U zit op {{ round($electricityPercentage) }}% van uw elektriciteitsbudget!
            @elseif($gasPercentage > $threshold)
                U zit op {{ round($gasPercentage) }}% van uw gasbudget!
            @endif
        </span>
    </div>
    <button id="closeNotification" class="ml-2 text-white hover:text-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>
</div>

@push('alert-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sluiten van de melding
        document.getElementById('closeNotification').addEventListener('click', function() {
            document.getElementById('budgetWarning').style.display = 'none';
        });
    });
</script>
@endpush