@props(['period', 'date', 'housingType'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6 bg-white border-b border-gray-200">
        <!-- Huidige geselecteerde datum/periode weergave -->
        <div class="mb-4 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">
                @switch($period)
                    @case('day')
                        Energieverbruik op {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
                        @break
                    @case('month')
                        Energieverbruik in {{ \Carbon\Carbon::parse($date)->format('F Y') }}
                        @break
                    @case('year')
                        Energieverbruik in {{ \Carbon\Carbon::parse($date)->format('Y') }}
                        @break
                    @default
                        Energieverbruik
                @endswitch
            </h2>
        </div>

        <div class="flex flex-col md:flex-row md:justify-between md:items-center space-y-4 md:space-y-0">
            <div>
                <h3 class="text-lg font-medium mb-2">Tijdsperiode</h3>
                <div class="flex space-x-4">
                    <a href="{{ route('energy.dashboard', ['period' => 'day', 'date' => $date, 'housing_type' => $housingType]) }}" 
                       class="px-4 py-2 rounded-md {{ $period === 'day' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        Dag
                    </a>
                    <a href="{{ route('energy.dashboard', ['period' => 'month', 'date' => $date, 'housing_type' => $housingType]) }}" 
                       class="px-4 py-2 rounded-md {{ $period === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        Maand
                    </a>
                    <a href="{{ route('energy.dashboard', ['period' => 'year', 'date' => $date, 'housing_type' => $housingType]) }}" 
                       class="px-4 py-2 rounded-md {{ $period === 'year' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        Jaar
                    </a>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-medium mb-2">Datumkiezer</h3>
                <div class="flex items-center space-x-2">
                    @switch($period)
                        @case('day')
                            <input type="date" id="datePicker" class="date-picker" value="{{ $date }}">
                            @break
                        @case('month')
                            <input type="month" id="datePicker" class="date-picker" value="{{ \Carbon\Carbon::parse($date)->format('Y-m') }}">
                            @break
                        @case('year')
                            <input type="number" id="datePicker" class="date-picker" value="{{ \Carbon\Carbon::parse($date)->format('Y') }}" min="2000" max="2050">
                            @break
                    @endswitch
                    <button id="applyDate" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Toepassen</button>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-medium mb-2">Woningtype</h3>
                <select id="housingType" class="px-4 py-2 rounded-md border border-gray-300">
                    <option value="appartement" {{ $housingType === 'appartement' ? 'selected' : '' }}>Appartement</option>
                    <option value="tussenwoning" {{ $housingType === 'tussenwoning' ? 'selected' : '' }}>Tussenwoning</option>
                    <option value="hoekwoning" {{ $housingType === 'hoekwoning' ? 'selected' : '' }}>Hoekwoning</option>
                    <option value="twee_onder_een_kap" {{ $housingType === 'twee_onder_een_kap' ? 'selected' : '' }}>2-onder-1-kap</option>
                    <option value="vrijstaand" {{ $housingType === 'vrijstaand' ? 'selected' : '' }}>Vrijstaand</option>
                </select>
            </div>
        </div>
        
        <!-- Navigatiepijlen voor eenvoudige datum navigatie -->
        <div class="flex justify-center mt-6">
            <a href="{{ route('energy.dashboard', ['period' => $period, 'date' => \Carbon\Carbon::parse($date)->sub(1, $period)->format('Y-m-d'), 'housing_type' => $housingType]) }}" 
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-l-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <a href="{{ route('energy.dashboard', ['period' => $period, 'date' => \Carbon\Carbon::now()->format('Y-m-d'), 'housing_type' => $housingType]) }}" 
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 mx-1">
                Vandaag
            </a>
            <a href="{{ route('energy.dashboard', ['period' => $period, 'date' => \Carbon\Carbon::parse($date)->add(1, $period)->format('Y-m-d'), 'housing_type' => $housingType]) }}" 
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-r-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Datum picker functionaliteit
        document.getElementById('applyDate').addEventListener('click', function() {
            const dateInput = document.getElementById('datePicker').value;
            const period = "{{ $period }}";
            let formattedDate;
            
            // Formatteer de datum op basis van het type input
            if (period === 'day') {
                formattedDate = dateInput; // Reeds in YYYY-MM-DD formaat
            } else if (period === 'month') {
                // Voor maandselectie (YYYY-MM formaat), zet om naar YYYY-MM-01
                formattedDate = dateInput + '-01';
            } else if (period === 'year') {
                // Voor jaarselectie (YYYY formaat), zet om naar YYYY-01-01
                formattedDate = dateInput + '-01-01';
            }
            
            window.location.href = "{{ route('energy.dashboard') }}?period=" + period + 
                                "&date=" + formattedDate + 
                                "&housing_type={{ $housingType }}";
        });

        // Woningtype selector
        document.getElementById('housingType').addEventListener('change', function() {
            window.location.href = "{{ route('energy.dashboard') }}?period={{ $period }}&date={{ $date }}&housing_type=" + this.value;
        });
    });
</script>
@endpush