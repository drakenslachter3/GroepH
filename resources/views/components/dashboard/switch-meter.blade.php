@props(['meters', 'selectedMeterId'])

<div class="w-full p-2">
    <div class="flex flex-col">
        <div class="flex flex-row justify-between items-center w-full">
            <h3 class="text-lg font-semibold dark:text-white">Selecteer een meter</h3>
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
                <label id="listbox-label" class="block text-sm font-medium text-gray-900 dark:text-white">Meterlijst</label>
                <select class="rounded px-2 py-1 w-full mb-2 dark:bg-gray-700" name="meter" id="meter-selector">
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
            </form>
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