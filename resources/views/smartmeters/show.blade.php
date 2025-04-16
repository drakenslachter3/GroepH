{{-- resources/views/smartmeters/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Slimme Meter Details</h2>
                    <div class="flex space-x-2">
                        <a href="{{  route('smartmeters.edit', $smartmeter->id) }}" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md shadow-sm flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Bewerken
                        </a>
                        <form method="POST" action="{{ route('smartmeters.destroy', ['smartmeter' => $smartmeter->id]) }}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Weet je zeker dat je deze slimme meter wilt verwijderen?');" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md shadow-sm flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Verwijderen
                            </button>
                        </form>
                    </div>
                </div>

                @if (session('status'))
                    <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Meter ID:</span>
                            <span class="mt-1">{{ $smartmeter->meter_id }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Metingen:</span>
                            <div class="flex space-x-2 mt-1">
                                @if($smartmeter->measures_electricity ?? false)
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Elektriciteit</span>
                                @endif
                                @if($smartmeter->measures_gas ?? false)
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Gas</span>
                                @endif
                                @if(!isset($smartmeter->measures_electricity) && !isset($smartmeter->measures_gas))
                                    <span class="mt-1">{{ ucfirst($smartmeter->type ?? 'onbekend') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Locatie:</span>
                            <span class="mt-1">{{ $smartmeter->location ?: 'Niet gespecificeerd' }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Status:</span>
                            <div class="mt-1">
                                @if($smartmeter->active)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Actief</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactief</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Installatiedatum:</span>
                            <span class="mt-1">{{ $smartmeter->installation_date ? $smartmeter->installation_date->format('d-m-Y') : 'Niet gespecificeerd' }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Laatste meetdatum:</span>
                            <span class="mt-1">{{ $smartmeter->last_reading_date ? $smartmeter->last_reading_date->format('d-m-Y H:i') : 'Nog geen meting' }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Gekoppelde gebruiker:</span>
                            <div class="mt-1">
                                @if($smartmeter->user)
                                    <div class="flex items-center">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 mr-2">Gekoppeld</span>
                                        <a href="{{ route('users.show', $smartmeter->user->id) }}" class="text-blue-600 hover:underline">
                                            {{ $smartmeter->user->name }} ({{ $smartmeter->user->email }})
                                        </a>
                                    </div>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Niet gekoppeld</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Aangemaakt op:</span>
                            <span class="mt-1">{{ $smartmeter->created_at->format('d-m-Y H:i') }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 pb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Laatst bijgewerkt:</span>
                            <span class="mt-1">{{ $smartmeter->updated_at->format('d-m-Y H:i') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Latest Meter Readings Section -->
                @php
                    $latestReading = $smartmeter->latestReading;
                @endphp

                <div class="mt-8">
                    <h3 class="text-lg font-medium mb-4">Laatste Meterstanden</h3>

                    @if($latestReading)
                        <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Electricity Readings -->
                                @if($smartmeter->measures_electricity ?? false)
                                <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg">
                                    <h4 class="font-medium text-blue-700 dark:text-blue-300 mb-2">Elektriciteit</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Afgenomen (Laag tarief):</span>
                                            <span class="font-medium">{{ number_format($latestReading->electricity_delivered_tariff1, 3) }} kWh</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Afgenomen (Hoog tarief):</span>
                                            <span class="font-medium">{{ number_format($latestReading->electricity_delivered_tariff2, 3) }} kWh</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Teruggeleverd (Laag tarief):</span>
                                            <span class="font-medium">{{ number_format($latestReading->electricity_returned_tariff1, 3) }} kWh</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Teruggeleverd (Hoog tarief):</span>
                                            <span class="font-medium">{{ number_format($latestReading->electricity_returned_tariff2, 3) }} kWh</span>
                                        </div>
                                        <div class="border-t border-blue-200 dark:border-blue-700 pt-2 mt-2">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-300">Huidig verbruik:</span>
                                                <span class="font-medium">{{ number_format($latestReading->current_electricity_usage, 3) }} kW</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-300">Huidige teruglevering:</span>
                                                <span class="font-medium">{{ number_format($latestReading->current_electricity_return, 3) }} kW</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Gas Readings -->
                                @if($smartmeter->measures_gas ?? false)
                                <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-lg">
                                    <h4 class="font-medium text-yellow-700 dark:text-yellow-300 mb-2">Gas</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Meterstand:</span>
                                            <span class="font-medium">{{ number_format($latestReading->gas_meter_reading, 3) }} m³</span>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Additional Data -->
                                <div class="bg-green-50 dark:bg-green-900/30 p-4 rounded-lg">
                                    <h4 class="font-medium text-green-700 dark:text-green-300 mb-2">Extra Gegevens</h4>
                                    <div class="space-y-2">
                                        @if(isset($latestReading->additional_data['s0']))
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-300">{{ $latestReading->additional_data['s0']['label'] ?? 'Sensor 0' }}:</span>
                                                <span class="font-medium">{{ $latestReading->additional_data['s0']['value'] ?? 0 }} {{ $latestReading->additional_data['s0']['unit'] ?? '' }}</span>
                                            </div>
                                        @endif
                                        @if(isset($latestReading->additional_data['s1']))
                                            <div class="flex justify-between">
                                                <span class="text-gray-600 dark:text-gray-300">{{ $latestReading->additional_data['s1']['label'] ?? 'Sensor 1' }}:</span>
                                                <span class="font-medium">{{ $latestReading->additional_data['s1']['value'] ?? 0 }} {{ $latestReading->additional_data['s1']['unit'] ?? '' }}</span>
                                            </div>
                                        @endif
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 dark:text-gray-300">Laatste meting:</span>
                                            <span class="font-medium">{{ $latestReading->timestamp->format('d-m-Y H:i:s') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded text-center">
                            <p>Er zijn nog geen metingen beschikbaar voor deze meter.</p>
                        </div>
                    @endif
                </div>

                <div class="mt-8">
                    <a href="{{ route('smartmeters.index') }}" class="px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Terug naar overzicht
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection