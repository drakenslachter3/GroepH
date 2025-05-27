
<div class="w-full p-2">
    <div class="flex flex-col">
        <div class="flex flex-row justify-between items-center w-full">
            <x-dashboard.widget-navigation :showPrevious="true" />
            <x-dashboard.widget-heading :title="'Netto resultante'" />
            <x-dashboard.widget-navigation :showNext="true" />
            <div class="tooltip relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 cursor-pointer hover:text-gray-600 dark:text-gray-300 dark:hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="tooltiptext invisible absolute z-10 px-3 py-2 text-sm bg-gray-800 text-white rounded shadow-lg -right-4 -bottom-20 w-48">
                    Voor u ziet u het netto resultante tussen geconsumeerde en geproduceerde energie.
                </span>
            </div>
        </div>
        <!-- Metrics -->
            <div class="space-y-2">
                <!-- Usage value -->
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Verbruik Gas:</span>
                    <span tabindex="0"
                        aria-label=""
                        class="font-bold dark:text-white">
                        0,00
                    </span>
                </div>
                <!-- Generated value -->
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Opgewekte Gas:</span>
                    <span tabindex="0"
                        aria-label=""
                        class="font-bold dark:text-white">
                        0,00
                    </span>
                </div>
                
                <!-- Target value -->
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Verbruik Elektriciteit:</span>
                    <span tabindex="0"
                        aria-label=""
                        class="font-bold dark:text-white">
                        0,00
                    </span>
                </div>
                <!-- Generated value -->
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Opgewekte Elektriciteit:</span>
                    <span tabindex="0"
                        aria-label=""
                        class="font-bold dark:text-white">
                        0,00
                    </span>
                </div>
            </div>
    </div>
</div>

