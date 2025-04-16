@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Slimme Meters Beheer voor {{ $user->name }}</h2>
                    <a href="{{ route('users.show', $user->id) }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Terug naar gebruiker
                    </a>
                </div>

                @if (session('status'))
                    <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Koppel Nieuwe Meter Sectie -->
                <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg mb-6">
                    <h3 class="text-lg font-medium mb-4">Koppel nieuwe meter</h3>
                    
                    <form method="POST" action="{{ route('smartmeters.linkMeter', $user->id) }}" class="space-y-4">
                        @csrf
                        <div class="relative">
                            <label for="meter_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zoek meter op ID</label>
                            <input type="text" id="meter_search" 
                                class="block w-full p-2 pl-10 border-gray-300 rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600" 
                                placeholder="Begin met typen om te zoeken..." autocomplete="off">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" style="top: 24px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <!-- Dropdown resultaten -->
                            <div id="search_results" class="hidden absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 rounded-md shadow-lg max-h-60 overflow-auto"></div>
                        </div>
                        
                        <!-- Geselecteerde meter -->
                        <div id="selected_meter" class="hidden border dark:border-gray-700 p-4 rounded-md bg-blue-50 dark:bg-blue-900">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium"><span id="selected_meter_id"></span></p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300"><span id="selected_meter_location"></span> - <span id="selected_meter_type"></span></p>
                                </div>
                                <button type="button" id="clear_selection" class="text-red-500 hover:text-red-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <input type="hidden" id="meter_id" name="meter_id">
                        
                        <div>
                            <button type="submit" id="submit_button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                Koppel meter aan gebruiker
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Huidige gekoppelde meters -->
                <h3 class="text-lg font-medium mb-4">Huidige gekoppelde meters</h3>
                
                @if($user->smartMeters->count() > 0)
                    <div class="overflow-x-auto relative">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="py-3 px-6">Meter ID</th>
                                    <th scope="col" class="py-3 px-6">Type</th>
                                    <th scope="col" class="py-3 px-6">Locatie</th>
                                    <th scope="col" class="py-3 px-6">Status</th>
                                    <th scope="col" class="py-3 px-6">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->smartMeters as $meter)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="py-4 px-6">{{ $meter->meter_id }}</td>
                                        <td class="py-4 px-6">{{ ucfirst($meter->type) }}</td>
                                        <td class="py-4 px-6">{{ $meter->location ?: 'Niet gespecificeerd' }}</td>
                                        <td class="py-4 px-6">
                                            @if($meter->active)
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Actief</span>
                                            @else
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactief</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 flex space-x-2">
                                            <a href="{{ route('smartmeters.show', $meter->id) }}" class="px-3 py-1 text-blue-500 hover:text-blue-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('smartmeters.unlinkMeter', ['user' => $user->id, 'smartmeter' => $meter->id]) }}">
                                                @csrf
                                                <button type="submit" onclick="return confirm('Weet je zeker dat je deze meter wilt ontkoppelen van de gebruiker?');" class="px-3 py-1 text-red-500 hover:text-red-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded text-center">
                        <p>Deze gebruiker heeft nog geen slimme meters gekoppeld.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const meterSearchInput = document.getElementById('meter_search');
        const searchResults = document.getElementById('search_results');
        const selectedMeter = document.getElementById('selected_meter');
        const selectedMeterId = document.getElementById('selected_meter_id');
        const selectedMeterLocation = document.getElementById('selected_meter_location');
        const selectedMeterType = document.getElementById('selected_meter_type');
        const meterIdInput = document.getElementById('meter_id');
        const clearSelectionBtn = document.getElementById('clear_selection');
        const submitButton = document.getElementById('submit_button');
        
        let debounceTimer;
        
        // Event listener voor input in zoekveld
        meterSearchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.innerHTML = '';
                searchResults.classList.add('hidden');
                return;
            }
            
            debounceTimer = setTimeout(() => {
                fetchResults(query);
            }, 300);
        });
        
        // Functie om resultaten op te halen
        function fetchResults(query) {
            fetch(`/api/smartmeters/search?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    displayResults(data);
                })
                .catch(error => {
                    console.error('Error fetching results:', error);
                });
        }
        
        // Functie om resultaten weer te geven
        function displayResults(meters) {
            searchResults.innerHTML = '';
            
            if (meters.length === 0) {
                const noResults = document.createElement('div');
                noResults.className = 'p-2 text-sm text-gray-700 dark:text-gray-300';
                noResults.textContent = 'Geen beschikbare meters gevonden';
                searchResults.appendChild(noResults);
            } else {
                meters.forEach(meter => {
                    const resultItem = document.createElement('div');
                    resultItem.className = 'p-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700';
                    resultItem.innerHTML = `
                        <div class="font-medium">${meter.meter_id}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">${meter.location || 'Geen locatie'} - ${meter.type === 'electricity' ? 'Elektriciteit' : 'Gas'}</div>
                    `;
                    
                    resultItem.addEventListener('click', () => {
                        selectMeter(meter);
                    });
                    
                    searchResults.appendChild(resultItem);
                });
            }
            
            searchResults.classList.remove('hidden');
        }
        
        // Functie om een meter te selecteren
        function selectMeter(meter) {
            meterIdInput.value = meter.id;
            selectedMeterId.textContent = meter.meter_id;
            selectedMeterLocation.textContent = meter.location || 'Geen locatie';
            selectedMeterType.textContent = meter.type === 'electricity' ? 'Elektriciteit' : 'Gas';
            
            selectedMeter.classList.remove('hidden');
            searchResults.classList.add('hidden');
            meterSearchInput.value = '';
            
            submitButton.disabled = false;
        }
        
        // Event listener voor het wissen van de selectie
        clearSelectionBtn.addEventListener('click', function() {
            meterIdInput.value = '';
            selectedMeter.classList.add('hidden');
            submitButton.disabled = true;
        });
        
        // Sluiten van resultaten wanneer ergens anders wordt geklikt
        document.addEventListener('click', function(event) {
            if (!meterSearchInput.contains(event.target) && !searchResults.contains(event.target)) {
                searchResults.classList.add('hidden');
            }
        });
    });
</script>
@endpush
@endsection