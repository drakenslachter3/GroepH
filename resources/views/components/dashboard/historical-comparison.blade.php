@props(['weekData', 'monthData', 'yearComparisonData'])

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg comparison-card">
        <div class="p-4 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
            <h4 class="font-medium text-gray-800 dark:text-white">Afgelopen Week</h4>
            <div class="flex justify-between mt-2">
                <div>
                    <p class="text-sm text-gray-600 dark:text-white">Elektriciteit</p>
                    <p class="font-bold">{{ $weekData['electricity'] }} kWh</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-white">Gas</p>
                    <p class="font-bold">{{ $weekData['gas'] }} m³</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg comparison-card">
        <div class="p-4 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
            <h4 class="font-medium text-gray-800 dark:text-white">Afgelopen Maand</h4>
            <div class="flex justify-between mt-2">
                <div>
                    <p class="text-sm text-gray-600 dark:text-white">Elektriciteit</p>
                    <p class="font-bold">{{ $monthData['electricity'] }} kWh</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-white">Gas</p>
                    <p class="font-bold">{{ $monthData['gas'] }} m³</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg comparison-card">
        <div class="p-4 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
            <h4 class="font-medium text-gray-800 dark:text-white">Zelfde Periode Vorig Jaar</h4>
            <div class="flex justify-between mt-2">
                <div>
                    <p class="text-sm text-gray-600 dark:text-white">Elektriciteit</p>
                    <p class="font-bold">{{ $yearComparisonData['electricity'] }} kWh</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-white">Gas</p>
                    <p class="font-bold">{{ $yearComparisonData['gas'] }} m³</p>
                </div>
            </div>
        </div>
    </div>
</div>