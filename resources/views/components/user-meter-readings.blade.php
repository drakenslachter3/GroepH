<!-- resources/views/components/user-meter-readings.blade.php -->
@props(['user'])

@if($user->smartMeters->count() > 0)
    <div class="bg-white shadow-lg rounded-lg border border-gray-100 mb-8 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center dark:bg-gray-700 dark:border-gray-600">
            <h2 class="font-semibold text-lg text-gray-800 dark:text-white">Mijn Slimme Meters</h2>
            <span class="text-xs text-gray-500 dark:text-gray-300">Actuele meetgegevens</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
            @foreach($user->smartMeters as $meter)
                @php
                    $latestReading = $meter->latestReading;
                @endphp
                
                <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200 dark:border-gray-700">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center dark:bg-gray-700 dark:border-gray-600">
                        <div class="flex items-center">
                            @if($meter->type == 'electricity')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05c-.867 3.14-.3 5.63 1.424 7.353.172.172.358.326.555.46.364.247.708.594 1.017 1.035.828 1.193 1.313 2.857 1.313 4.802 0 .47.16.897.43 1.187.3.32.766.498 1.247.498h3.872c.535 0 1.023-.18 1.348-.499.294-.29.457-.675.457-1.186 0-1.477.356-2.824.863-3.903.33-.695.713-1.28 1.175-1.736.294-.29.59-.494.78-.635l.116-.07c.16-.127.294-.225.387-.296a1 1 0 00.17-1.596c-.45-.308-1.13-.503-1.918-.583a12.99 12.99 0 01-1.647-.313 1 1 0 10-.53 1.928c.146.04.285.083.476.13.165.047.362.094.608.146.155.031.347.063.57.094.395.054.721.161.873.314a7.537 7.537 0 00-.4.492c-.595.733-1.088 1.549-1.477 2.439-.173.318-.333.65-.477.983H15a1 1 0 00-.867.5 1 1 0 01-.133.25c-.138.21-.313.526-.448 1.02-.121.438-.232 1.023-.232 1.74a4.02 4.02 0 01-.15.596c-.156.26-.527.499-1.084.499h-3.872a.278.278 0 01-.221-.1.696.696 0 01-.093-.25c0-1.603-.402-2.95-1.035-3.863-.512-.738-1.153-1.296-1.734-1.644a3.948 3.948 0 01-.851-.682c-1.163-1.162-1.572-2.98-.841-5.476.202-.69.413-1.345.634-1.977.226-.64.455-1.227.676-1.74a10.97 10.97 0 01.36-.82 1 1 0 00-.17-1.044z" clip-rule="evenodd" />
                                </svg>
                            @endif
                            <h3 class="font-medium text-gray-800 dark:text-white">{{ $meter->getTypeDisplayName() }}</h3>
                        </div>
                        <span class="px-2 py-1 text-xs rounded {{ $meter->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $meter->active ? 'Actief' : 'Inactief' }}
                        </span>
                    </div>
                    
                    <div class="p-4">
                        <div class="mb-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Meter ID</p>
                            <p class="font-mono text-gray-800 dark:text-white">{{ $meter->meter_id }}</p>
                        </div>
                        
                        @if($latestReading)
                            <div class="mb-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Laatste Meting</p>
                                <p class="text-gray-800 dark:text-white">{{ $latestReading->timestamp->format('d-m-Y H:i:s') }}</p>
                            </div>
                            
                            @if($meter->type == 'electricity')
                                <div class="grid grid-cols-2 gap-3 mt-4">
                                    <div class="border border-gray-100 rounded p-3 dark:border-gray-700">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Huidig Verbruik</p>
                                        <p class="font-semibold text-lg text-blue-600">{{ number_format($latestReading->current_electricity_usage, 3) }} <span class="text-xs text-gray-500">kW</span></p>
                                    </div>
                                    
                                    <div class="border border-gray-100 rounded p-3 dark:border-gray-700">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Teruglevering</p>
                                        <p class="font-semibold text-lg text-green-600">{{ number_format($latestReading->current_electricity_return, 3) }} <span class="text-xs text-gray-500">kW</span></p>
                                    </div>
                                    
                                    <div class="border border-gray-100 rounded p-3 dark:border-gray-700">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Totaal Laag Tarief</p>
                                        <p class="font-semibold text-blue-600">{{ number_format($latestReading->electricity_delivered_tariff1, 3) }} <span class="text-xs text-gray-500">kWh</span></p>
                                    </div>
                                    
                                    <div class="border border-gray-100 rounded p-3 dark:border-gray-700">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Totaal Hoog Tarief</p>
                                        <p class="font-semibold text-blue-600">{{ number_format($latestReading->electricity_delivered_tariff2, 3) }} <span class="text-xs text-gray-500">kWh</span></p>
                                    </div>
                                </div>
                            @else
                                <div class="border border-gray-100 rounded p-3 mt-4 dark:border-gray-700">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Gasmeterstand</p>
                                    <p class="font-semibold text-lg text-blue-600">{{ number_format($latestReading->gas_meter_reading, 3) }} <span class="text-xs text-gray-500">m³</span></p>
                                </div>
                            @endif
                            
                        @else
                            <div class="p-4 text-center border rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600">
                                <p class="text-gray-500 dark:text-gray-400">Geen metingen beschikbaar</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif