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
                        <!-- Alpine.js Smart Meter Selector -->
                        <div class="relative" x-data="smartMeterSelector">
                            <!-- Hidden input to store the selected smart meter ID -->
                            <input type="hidden" name="meter_id" :value="selectedMeter ? selectedMeter.id : ''" />
                            
                            <!-- Search input field -->
                            <div class="flex">
                                <input 
                                    type="text" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Zoek een slimme meter... (ID of locatie)"
                                    @focus="open = true"
                                    @click.outside="open = false"
                                    x-model="search"
                                    :value="selectedMeter ? selectedMeter.meter_id + ' - ' + selectedMeter.location : ''"
                                />
                                <button type="button" class="ml-2 mt-1 bg-indigo-600 p-2 rounded-md text-white" @click="open = !open">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Results dropdown -->
                            <div 
                                x-show="open" 
                                class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg rounded-md py-1 text-base overflow-auto focus:outline-none max-h-60"
                                style="display: none;"
                            >
                                <div x-show="!filteredMeters().length" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                    Geen slimme meters gevonden
                                </div>
                                
                                <template x-for="meter in filteredMeters()" :key="meter.id">
                                    <div 
                                        @click="selectedMeter = meter; open = false; search = meter.meter_id + ' - ' + meter.location;"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-100 dark:hover:bg-indigo-900"
                                        :class="selectedMeter && selectedMeter.id === meter.id ? 'bg-indigo-50 dark:bg-indigo-800' : ''"
                                    >
                                        <div class="flex items-center">
                                            <span class="block truncate text-gray-900 dark:text-white" x-text="meter.meter_id + ' - ' + meter.location"></span>
                                        </div>
                                        <span 
                                            x-show="selectedMeter && selectedMeter.id === meter.id" 
                                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600 dark:text-indigo-400"
                                        >
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Selected smart meter display -->
                            <div x-show="selectedMeter" class="mt-2 p-2 bg-indigo-50 dark:bg-indigo-900 rounded-md flex justify-between items-center">
                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="selectedMeter ? 'Geselecteerd: ' + selectedMeter.meter_id + ' - ' + selectedMeter.location : ''"></span>
                                <button type="button" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800" @click="selectedMeter = null; search = '';">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" x-bind:disabled="!selectedMeter">
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
                                    <th scope="col" class="py-3 px-6">Naam</th>
                                    <th scope="col" class="py-3 px-6">Meet Types</th>
                                    <th scope="col" class="py-3 px-6">Locatie</th>
                                    <th scope="col" class="py-3 px-6">Status</th>
                                    <th scope="col" class="py-3 px-6">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->smartMeters as $meter)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="py-4 px-6">{{ $meter->meter_id }}</td>
                                        <td class="py-4 px-6">{{ $meter->name ?? 'Niet gespecificeerd' }}</td>
                                        <td class="py-4 px-6">
                                            <div class="flex space-x-2">
                                                @if($meter->measures_electricity ?? false)
                                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Elektriciteit</span>
                                                @endif
                                                @if($meter->measures_gas ?? false)
                                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Gas</span>
                                                @endif
                                                @if(!($meter->measures_electricity ?? false) && !($meter->measures_gas ?? false))
                                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                                        {{ $meter->type ?? 'Onbekend' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
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
    document.addEventListener('alpine:init', () => {
        Alpine.data('smartMeterSelector', () => ({
            init() {
                // Leave this empty as we'll fetch meters dynamically
            },
            open: false,
            selectedMeter: null,
            search: '',
            smartMeters: [],
            
            // Fetch meters when dropdown opens
            async fetchMeters() {
                if (this.smartMeters.length === 0) {
                    try {
                        const response = await fetch('/api/smartmeters/search?query=' + encodeURIComponent(this.search));
                        const data = await response.json();
                        this.smartMeters = data.map(meter => ({
                            id: meter.id,
                            name: meter.name,
                            meter_id: meter.meter_id, 
                            location: meter.location || 'Geen locatie',
                            type: meter.type
                        }));
                    } catch (error) {
                        console.error('Error fetching smart meters:', error);
                    }
                }
            },
            
            filteredMeters() {
                // Fetch meters if we open the dropdown and have none
                if (this.open && this.smartMeters.length === 0) {
                    this.fetchMeters();
                }
                
                if (!this.search) return this.smartMeters;
                
                const searchLower = this.search.toLowerCase();
                return this.smartMeters.filter(meter => 
                    meter.meter_id.toLowerCase().includes(searchLower) || 
                    meter.location.toLowerCase().includes(searchLower)
                );
            }
        }));
    });
</script>
@endpush
@endsection