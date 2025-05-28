@props(['budgetStatus'])

@if($budgetStatus['needs_setup'])
<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6" role="alert" aria-live="polite">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">
                Budget instelling vereist
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
                <p>
                    U heeft {{ $budgetStatus['meters_needing_setup'] }} van de {{ $budgetStatus['total_meters'] }} 
                    slimme meter(s) waarvoor nog geen energiebudget is ingesteld:
                </p>
                <ul class="list-disc pl-5 mt-2 space-y-1">
                    @foreach($budgetStatus['meters_needing_setup_list'] as $meter)
                        <li>
                            <strong>{{ $meter->name }}</strong> 
                            ({{ $meter->meter_id }})
                            @if($meter->location)
                                - {{ $meter->location }}
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3">
                    Stel budgetten in om uw energieverbruik te kunnen monitoren en optimaliseren.
                </p>
            </div>
            <div class="mt-4">
                <div class="-mx-2 -my-1.5 flex">
                    <a href="{{ route('budget.form') }}" 
                       class="bg-yellow-50 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-yellow-50 focus:ring-yellow-600">
                        Budget instellen
                    </a>
                    <button type="button" 
                            onclick="this.closest('[role=alert]').style.display='none'"
                            class="ml-3 bg-yellow-50 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-yellow-50 focus:ring-yellow-600">
                        Later
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@elseif($budgetStatus['total_meters'] > 0)
<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6" role="alert" aria-live="polite">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-green-800">
                Budgetten zijn ingesteld
            </h3>
            <div class="mt-2 text-sm text-green-700">
                <p>
                    Alle {{ $budgetStatus['total_meters'] }} slimme meter(s) hebben een energiebudget ingesteld. 
                    U kunt nu optimaal gebruik maken van het dashboard.
                </p>
            </div>
            <div class="mt-4">
                <div class="-mx-2 -my-1.5 flex">
                    <a href="{{ route('budget.form') }}" 
                       class="bg-green-50 px-2 py-1.5 rounded-md text-sm font-medium text-green-800 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600">
                        Budget aanpassen
                    </a>
                    <button type="button" 
                            onclick="this.closest('[role=alert]').style.display='none'"
                            class="ml-3 bg-green-50 px-2 py-1.5 rounded-md text-sm font-medium text-green-800 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600">
                        Sluiten
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif