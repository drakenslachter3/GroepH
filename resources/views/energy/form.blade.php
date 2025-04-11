<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Stel je jaarlijkse budget in') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Left Panel: Yearly Budget (1) -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-lg mb-4">{{ __('Jaarbudget') }}</h3>

                        <form method="POST" action="{{ route('budget.store') }}">
                            @csrf
                            <div class="mb-6">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Elektriciteit</label>
                                <div class="flex">
                                    <input type="number" step="0.01" name="electricity_value"
                                        id="yearly_electricity_budget"
                                        class="p-2 border dark:border-gray-600 rounded-l w-2/3 dark:bg-gray-700 dark:text-gray-200"
                                        value="{{ $yearlyBudget->electricity_target_kwh ?? old('electricity_value') }}"
                                        required>
                                    <span
                                        class="p-2 border dark:border-gray-600 border-l-0 rounded-r bg-gray-50 dark:bg-gray-700 dark:text-gray-200 w-1/3 flex items-center justify-center">kWh</span>
                                </div>
                            </div>

                            <div class="mb-6">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Gas</label>
                                <div class="flex">
                                    <input type="number" step="0.01" name="gas_value" id="yearly_gas_budget"
                                        class="p-2 border dark:border-gray-600 rounded-l w-2/3 dark:bg-gray-700 dark:text-gray-200"
                                        value="{{ $yearlyBudget->gas_target_m3 ?? old('gas_value') }}" required>
                                    <span
                                        class="p-2 border dark:border-gray-600 border-l-0 rounded-r bg-gray-50 dark:bg-gray-700 dark:text-gray-200 w-1/3 flex items-center justify-center">m³</span>
                                </div>
                            </div>

                            <div class="flex flex-col space-y-4">
                                <button type="button" id="resetButton"
                                    class="w-full px-4 py-2 bg-gray-200 text-gray-800 rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                    Reset
                                </button>

                                <button type="submit"
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Opslaan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Panel: Monthly Budgets (2, 3, 4) -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg md:col-span-2"
                    x-data="monthlyBudget()">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <!-- Utility Toggle (4) -->
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-semibold text-lg">{{ __('Maandelijks budget') }}</h3>
                            <div class="relative">
                                <button @click="toggleUtility()"
                                    class="relative px-4 py-2 bg-white border rounded-md shadow-sm w-40 text-center font-medium">
                                    <span x-text="activeUtility === 'gas' ? 'm³' : 'kWh'"
                                        class="text-sm font-medium"></span>
                                </button>
                                <div class="absolute top-0 right-0 mt-1 mr-1 text-xs text-gray-500">4.</div>
                            </div>
                        </div>

                        <!-- Year Total Display -->
                        <div class="mb-4 text-sm text-gray-500">
                            <span>Jaarlijks totaal: </span>
                            <span
                                x-text="activeUtility === 'gas' ? yearlyGasBudget + ' m³' : yearlyElectricityBudget + ' kWh'"></span>
                            <span x-text="'(' + getTotalUsed() + ' gebruikt)'"></span>
                        </div>

                        <form method="POST" action="{{ route('budget.monthly.update') }}" id="monthlyBudgetForm">
                            @csrf

                            <!-- Monthly Budget Sliders (3) -->
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                                <template x-for="(month, index) in months" :key="index">
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        <div class="mb-2 flex justify-between items-center">
                                            <span class="font-medium" x-text="month"></span>
                                            <span class="text-sm"
                                                x-text="formatValue(activeUtility === 'gas' ? gasData[index].value : electricityData[index].value)"></span>
                                        </div>

                                        <input type="hidden" :name="`budgets[${index}][id]`"
                                            :value="activeUtility === 'gas' ? gasData[index].id : electricityData[index].id">

                                        <input type="hidden" :name="`budgets[${index}][month]`" :value="index + 1">

                                        <input type="hidden" :name="`budgets[${index}][gas_target_m3]`"
                                            :value="gasData[index].value" x-model="gasData[index].value">

                                        <input type="hidden" :name="`budgets[${index}][electricity_target_kwh]`"
                                            :value="electricityData[index].value"
                                            x-model="electricityData[index].value">

                                        <!-- Slider -->
                                        <input type="range"
                                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                            :min="0" :max="getMaximumValue()" step="0.1"
                                            :value="activeUtility === 'gas' ? gasData[index].value : electricityData[index]
                                                .value"
                                            :disabled="activeUtility === 'gas' ? gasData[index].locked : electricityData[
                                                index].locked"
                                            @input="handleSliderChange(index, $event.target.value)">

                                        <!-- Lock toggle -->
                                        <div class="flex items-center justify-center mt-2">
                                            <button type="button" class="w-4 h-4 rounded-sm"
                                                :class="(activeUtility === 'gas' ? gasData[index].locked : electricityData[
                                                    index].locked) ? 'bg-gray-400' : 'bg-white border border-gray-300'"
                                                @click="toggleLock(index)"></button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Control Buttons (2) -->
                            <div class="flex justify-between">
                                <button type="button" @click="resetCurrentUtility()"
                                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md text-sm">
                                    Reset
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm">
                                    Opslaan
                                </button>
                                <div class="absolute bottom-0 right-0 mb-1 mr-1 text-xs text-gray-500">3.</div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            function monthlyBudget() {
                return {
                    activeUtility: 'electricity', // 'gas' or 'electricity'
                    months: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],

                    gasData: @json(
                        $monthlyBudgets
                            ? array_map(function ($budget) {
                                return [
                                    'id' => $budget->id ?? null,
                                    'month' => $budget->month ?? null,
                                    'value' => $budget->gas_target_m3 ?? 0,
                                    'locked' => false,
                                ];
                            }, $monthlyBudgets->toArray())
                            : array_map(function ($month) {
                                return ['id' => null, 'month' => $month, 'value' => 0, 'locked' => false];
                            }, range(1, 12))),

                    electricityData: @json(
                        $monthlyBudgets
                            ? array_map(function ($budget) {
                                return [
                                    'id' => $budget->id ?? null,
                                    'month' => $budget->month ?? null,
                                    'value' => $budget->electricity_target_kwh ?? 0,
                                    'locked' => false,
                                ];
                            }, $monthlyBudgets->toArray())
                            : array_map(function ($month) {
                                return ['id' => null, 'month' => $month, 'value' => 0, 'locked' => false];
                            }, range(1, 12))),

                    yearlyGasBudget: @json($yearlyBudget->gas_target_m3 ?? 1200),
                    yearlyElectricityBudget: @json($yearlyBudget->electricity_target_kwh ?? 3500),

                    init() {
                        // Initialize with evenly distributed values if no values exist
                        if (this.getTotalUsed() === 0) {
                            this.resetCurrentUtility();
                        }

                        // Monitor yearly budget inputs to update sliders
                        document.getElementById('yearly_electricity_budget').addEventListener('change', (e) => {
                            this.yearlyElectricityBudget = parseFloat(e.target.value);
                            if (this.activeUtility === 'electricity') {
                                this.redistributeProportionally();
                            }
                        });

                        document.getElementById('yearly_gas_budget').addEventListener('change', (e) => {
                            this.yearlyGasBudget = parseFloat(e.target.value);
                            if (this.activeUtility === 'gas') {
                                this.redistributeProportionally();
                            }
                        });

                        document.getElementById('resetButton').addEventListener('click', () => {
                            // Reset both yearly inputs
                            document.getElementById('yearly_electricity_budget').value = 3500;
                            document.getElementById('yearly_gas_budget').value = 1200;
                            this.yearlyElectricityBudget = 3500;
                            this.yearlyGasBudget = 1200;

                            // Reset all monthly data
                            this.resetAllData();
                        });
                    },

                    toggleUtility() {
                        this.activeUtility = this.activeUtility === 'gas' ? 'electricity' : 'gas';
                    },

                    handleSliderChange(index, newValue) {
                        const data = this.activeUtility === 'gas' ? this.gasData : this.electricityData;

                        // Store original value to calculate difference
                        const originalValue = data[index].value;

                        // Update with new value
                        data[index].value = parseFloat(newValue);

                        // Check if we're exceeding yearly budget
                        this.redistributeExcess();
                    },

                    toggleLock(index) {
                        if (this.activeUtility === 'gas') {
                            this.gasData[index].locked = !this.gasData[index].locked;
                        } else {
                            this.electricityData[index].locked = !this.electricityData[index].locked;
                        }
                    },

                    resetCurrentUtility() {
                        const data = this.activeUtility === 'gas' ? this.gasData : this.electricityData;
                        const yearlyBudget = this.activeUtility === 'gas' ? this.yearlyGasBudget : this.yearlyElectricityBudget;

                        // Set all values to yearlyBudget / 12 and unlock them
                        const defaultValue = yearlyBudget / 12;
                        data.forEach((month, index) => {
                            data[index].value = parseFloat(defaultValue.toFixed(1));
                            data[index].locked = false;
                        });
                    },

                    resetAllData() {
                        // Reset both gas and electricity data
                        const defaultElectricityValue = this.yearlyElectricityBudget / 12;
                        const defaultGasValue = this.yearlyGasBudget / 12;

                        this.electricityData.forEach((month, index) => {
                            this.electricityData[index].value = parseFloat(defaultElectricityValue.toFixed(1));
                            this.electricityData[index].locked = false;
                        });

                        this.gasData.forEach((month, index) => {
                            this.gasData[index].value = parseFloat(defaultGasValue.toFixed(1));
                            this.gasData[index].locked = false;
                        });
                    },

                    getMaximumValue() {
                        // Set max to approximately half of yearly budget
                        const yearlyBudget = this.activeUtility === 'gas' ? this.yearlyGasBudget : this.yearlyElectricityBudget;
                        return yearlyBudget / 6; // Allow sliders to go significantly higher than average
                    },

                    getTotalUsed() {
                        const data = this.activeUtility === 'gas' ? this.gasData : this.electricityData;
                        const total = data.reduce((sum, month) => sum + parseFloat(month.value), 0);
                        return parseFloat(total.toFixed(1));
                    },

                    formatValue(value) {
                        return this.activeUtility === 'gas' ?
                            `${parseFloat(value).toFixed(1)} m³` :
                            `${parseFloat(value).toFixed(1)} kWh`;
                    },

                    redistributeExcess() {
                        const data = this.activeUtility === 'gas' ? this.gasData : this.electricityData;
                        const yearlyBudget = this.activeUtility === 'gas' ? this.yearlyGasBudget : this.yearlyElectricityBudget;

                        // Calculate current total
                        const currentTotal = data.reduce((sum, month) => sum + parseFloat(month.value), 0);

                        // Check if we're over budget
                        if (currentTotal <= yearlyBudget) return;

                        // Calculate excess
                        const excess = currentTotal - yearlyBudget;

                        // Count unlocked months
                        const unlockedMonths = data.filter(month => !month.locked);

                        if (unlockedMonths.length === 0) return; // All locked, can't redistribute

                        // Calculate current total of unlocked months
                        const unlockedTotal = unlockedMonths.reduce((sum, month) => sum + parseFloat(month.value), 0);

                        // Redistribute excess proportionally among unlocked months
                        data.forEach((month, index) => {
                            if (!month.locked) {
                                const share = month.value / unlockedTotal;
                                const reduction = excess * share;
                                data[index].value = Math.max(0, parseFloat((month.value - reduction).toFixed(1)));
                            }
                        });
                    },

                    redistributeProportionally() {
                        const data = this.activeUtility === 'gas' ? this.gasData : this.electricityData;
                        const yearlyBudget = this.activeUtility === 'gas' ? this.yearlyGasBudget : this.yearlyElectricityBudget;

                        // Calculate current total
                        const currentTotal = data.reduce((sum, month) => sum + parseFloat(month.value), 0);

                        // Skip if current total is 0 (initial state)
                        if (currentTotal === 0) {
                            this.resetCurrentUtility();
                            return;
                        }

                        // Calculate factor to adjust all values
                        const adjustmentFactor = yearlyBudget / currentTotal;

                        // Apply factor to all months
                        data.forEach((month, index) => {
                            if (!month.locked) {
                                data[index].value = parseFloat((month.value * adjustmentFactor).toFixed(1));
                            }
                        });

                        // Check if we need to redistribute locked values
                        this.redistributeExcess();
                    }
                };
            }
        </script>
    @endpush
</x-app-layout>
