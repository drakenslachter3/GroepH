<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Maandelijks Budget Beheer') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Left Panel: Yearly Budget (1) -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-lg mb-4">{{ __('Jaarbudget') }}</h3>
                        
                        <form method="POST" action="{{ route('budget.form') }}">
                            @csrf
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Budget Gas</label>
                                <div class="flex">
                                    <input type="number"
                                        step="0.01"
                                        name="gas_value"
                                        id="yearly_gas_budget"
                                        class="p-2 border dark:border-gray-600 rounded-l w-2/3 dark:bg-gray-700 dark:text-gray-200"
                                        value="{{ $yearlyBudget->gas_target_m3 ?? old('gas_value') }}"
                                        required>
                                    <span class="p-2 border dark:border-gray-600 border-l-0 rounded-r bg-gray-50 dark:bg-gray-700 dark:text-gray-200 w-1/3 flex items-center justify-center">m³</span>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Budget Elektriciteit</label>
                                <div class="flex">
                                    <input type="number"
                                        step="0.01"
                                        name="electricity_value"
                                        id="yearly_electricity_budget"
                                        class="p-2 border dark:border-gray-600 rounded-l w-2/3 dark:bg-gray-700 dark:text-gray-200"
                                        value="{{ $yearlyBudget->electricity_target_kwh ?? old('electricity_value') }}"
                                        required>
                                    <span class="p-2 border dark:border-gray-600 border-l-0 rounded-r bg-gray-50 dark:bg-gray-700 dark:text-gray-200 w-1/3 flex items-center justify-center">kWh</span>
                                </div>
                            </div>
                            
                            <div class="flex">
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Jaarbudget Opslaan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Right Panel: Monthly Budgets (2, 3, 4) -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg md:col-span-2" x-data="monthlyBudget()">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <!-- Utility Toggle (4) -->
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-semibold text-lg">{{ __('Maandbudget') }}</h3>
                            <div class="flex items-center space-x-2">
                                <span x-text="activeUtility === 'gas' ? 'Gas (m³)' : 'Elektriciteit (kWh)'" class="text-sm font-medium"></span>
                                <button @click="toggleUtility()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm">
                                    <span x-text="activeUtility === 'gas' ? 'Toon Elektriciteit' : 'Toon Gas'"></span>
                                </button>
                            </div>
                        </div>
                        
                        <form method="POST" action="{{ route('budget.monthly.update') }}" id="monthlyBudgetForm">
                            @csrf
                            
                            <!-- Monthly Budget Sliders (3) -->
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                                <template x-for="(month, index) in months" :key="index">
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        <div class="mb-2 flex justify-between">
                                            <span class="font-medium" x-text="month"></span>
                                            <span class="text-sm" x-text="formatValue(activeUtility === 'gas' ? gasData[index].value : electricityData[index].value)"></span>
                                        </div>
                                        
                                        <input type="hidden" 
                                            :name="`budgets[${index}][id]`" 
                                            :value="activeUtility === 'gas' ? gasData[index].id : electricityData[index].id">
                                        
                                        <input type="hidden" 
                                            :name="`budgets[${index}][month]`" 
                                            :value="index + 1">
                                        
                                        <input type="hidden" 
                                            :name="`budgets[${index}][gas_target_m3]`" 
                                            :value="gasData[index].value"
                                            x-model="gasData[index].value">
                                            
                                        <input type="hidden" 
                                            :name="`budgets[${index}][electricity_target_kwh]`" 
                                            :value="electricityData[index].value"
                                            x-model="electricityData[index].value">
                                        
                                        <!-- Slider -->
                                        <input 
                                            type="range" 
                                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                            :min="0"
                                            :max="getMaximumValue()"
                                            step="0.1"
                                            :value="activeUtility === 'gas' ? gasData[index].value : electricityData[index].value"
                                            @input="handleSliderChange(index, $event.target.value)"
                                        >
                                        
                                        <!-- Lock toggle -->
                                        <div class="flex items-center mt-2">
                                            <label class="inline-flex relative items-center cursor-pointer">
                                                <input 
                                                    type="checkbox" 
                                                    class="sr-only peer" 
                                                    :checked="activeUtility === 'gas' ? gasData[index].locked : electricityData[index].locked"
                                                    @click="toggleLock(index)"
                                                >
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300" x-text="(activeUtility === 'gas' ? gasData[index].locked : electricityData[index].locked) ? 'Vast' : 'Variabel'"></span>
                                            </label>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Control Buttons (2) -->
                            <div class="flex justify-between">
                                <button type="button" @click="resetCurrentUtility()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md text-sm">
                                    Reset
                                </button>
                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm">
                                    Opslaan
                                </button>
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
                activeUtility: 'gas', // 'gas' or 'electricity'
                months: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'],
                
                gasData: @json(array_map(function($budget) {
                    return [
                        'id' => $budget->id,
                        'month' => $budget->month,
                        'value' => $budget->gas_target_m3,
                        'locked' => false
                    ];
                }, $monthlyBudgets->toArray())),
                
                electricityData: @json(array_map(function($budget) {
                    return [
                        'id' => $budget->id,
                        'month' => $budget->month,
                        'value' => $budget->electricity_target_kwh,
                        'locked' => false
                    ];
                }, $monthlyBudgets->toArray())),
                
                yearlyGasBudget: @json($yearlyBudget->gas_target_m3 ?? 0),
                yearlyElectricityBudget: @json($yearlyBudget->electricity_target_kwh ?? 0),
                
                toggleUtility() {
                    this.activeUtility = this.activeUtility === 'gas' ? 'electricity' : 'gas';
                },
                
                handleSliderChange(index, newValue) {
                    const data = this.activeUtility === 'gas' ? this.gasData : this.electricityData;
                    data[index].value = parseFloat(newValue);
                    
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
                
                getMaximumValue() {
                    // Half of yearly budget as maximum
                    const yearlyBudget = this.activeUtility === 'gas' ? this.yearlyGasBudget : this.yearlyElectricityBudget;
                    return yearlyBudget / 2;
                },
                
                formatValue(value) {
                    return this.activeUtility === 'gas' 
                        ? `${parseFloat(value).toFixed(1)} m³` 
                        : `${parseFloat(value).toFixed(1)} kWh`;
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
                }
            };
        }
    </script>
    @endpush
</x-app-layout>