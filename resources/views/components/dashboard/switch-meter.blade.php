@props(['title', 'meters', 'selectedMeterId'])

<section class="w-full p-2" aria-labelledby="switch-widget-title">
    <div class="flex flex-col">
        <div class="flex flex-row justify-between items-center w-full">
            <x-dashboard.widget-navigation :showPrevious="true" />
            <x-dashboard.widget-heading :title="$title" />
            <x-dashboard.widget-navigation :showNext="true" />
            
            <div class="tooltip relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 cursor-pointer hover:text-gray-600 dark:text-gray-300 dark:hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="tooltiptext invisible absolute z-10 px-3 py-2 text-sm bg-gray-800 text-white rounded shadow-lg -right-4 -bottom-20 w-48">
                    Selecteer een meter uit de lijst. Het dashboard wordt dan automatisch bijgewerkt met de bijbehorende gegevens.
                </span>
            </div>
        </div>

        <div class="mt-5">
            <form action="{{ route('dashboard.saveSelectedMeter') }}" method="POST">
                @csrf
                {{-- <label id="listbox-label" class="block text-sm font-medium text-gray-900">Meterlijst</label> --}}
                <label for="meter-select" id="listbox-label" class="block text-sm font-medium text-gray-900 dark:text-white">
                    Meterlijst
                </label>
        
                <select id="meter-selector"
                    name="meter"
                    class="rounded px-2 py-1 w-full mb-2 dark:bg-gray-700"
                    aria-describedby="meter-help"
                    aria-label="Selecteer een meter uit de lijst">
                
                    @if($meters->isEmpty())
                        <option value="">Nog geen meters gekoppeld</option>
                    @else
                        @foreach ($meters as $meter)
                            <option value="{{ $meter->id }}" {{ $meter->id == $selectedMeterId ? 'selected' : '' }}>
                                {{ $meter->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
                <p id="meter-help" class="sr-only">
                    Kies een meter om gegevens weer te geven of instellingen aan te passen.
                </p>
            </form>
        </div>
    </div>
</section>
