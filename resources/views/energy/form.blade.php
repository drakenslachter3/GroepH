<x-app-layout>
    <x-slot name="header">
        <h1 tabindex="0" class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Stel energiebudget per meter in') }}
        </h1>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert" aria-live="polite">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert" aria-live="assertive">
                {{ session('error') }}
            </div>
        @endif

        @if($smartMeters->isEmpty())
            <!-- No Smart Meters Available -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-14 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">Geen slimme meters beschikbaar</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        U heeft nog geen slimme meters gekoppeld aan uw account. Neem contact op met de beheerder om meters toe te voegen.
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Terug naar Dashboard
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Introduction text -->
            <div class="mb-6">
                <p tabindex="0" class="text-gray-700 dark:text-gray-300">
                    Stel hieronder het energiebudget in voor elke gekoppelde slimme meter. U kunt het jaarlijkse budget instellen en de verdeling over de maanden aanpassen.
                </p>
            </div>

            <form id="meterBudgetsForm" method="POST" action="{{ route('budget.store-per-meter') }}" novalidate>
                @csrf
                
                <!-- Meters Grid -->
                <div class="space-y-8">
                    @foreach($smartMeters as $index => $meter)
                    @php
                        $meterBudget = $existingBudgets->where('smart_meter_id', $meter->id)->first();
                        $monthlyBudgets = $meterBudget ? $meterBudget->monthlyBudgets : collect();
                    @endphp
                    
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg meter-budget-section" data-meter-id="{{ $meter->id }}">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <!-- Meter Header -->
                            <div class="mb-6">
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                    {{ $meter->name }}
                                </h2>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <p><strong>Meter ID:</strong> {{ $meter->meter_id }}</p>
                                    @if($meter->location)
                                        <p><strong>Locatie:</strong> {{ $meter->location }}</p>
                                    @endif
                                    <p><strong>Meet:</strong> 
                                        @if($meter->measures_electricity && $meter->measures_gas)
                                            Elektriciteit en Gas
                                        @elseif($meter->measures_electricity)
                                            Alleen Elektriciteit
                                        @elseif($meter->measures_gas)
                                            Alleen Gas
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="grid md:grid-cols-3 gap-6">
                                <!-- Yearly Budget Section -->
                                <section class="md:col-span-1" aria-labelledby="yearly-budget-heading-{{ $meter->id }}">
                                    <h3 id="yearly-budget-heading-{{ $meter->id }}" class="font-semibold text-lg mb-4 dark:text-gray-200">
                                        {{ __('Jaarbudget') }}
                                    </h3>

                                    @if($meter->measures_electricity)
                                    <div class="mb-6">
                                        <label for="electricity_input_{{ $meter->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Elektriciteit
                                            <span class="sr-only">(in kilowatt-uur)</span>
                                        </label>
                                        <div class="flex">
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                name="meters[{{ $meter->id }}][electricity_target_kwh]" 
                                                id="electricity_input_{{ $meter->id }}" 
                                                class="p-2 border dark:border-gray-600 rounded-l w-2/3 dark:bg-gray-700 dark:text-gray-300"
                                                value="{{ $meterBudget->electricity_target_kwh ?? 1750 }}"
                                                required
                                            >
                                            <span class="p-2 border dark:border-gray-600 border-l-0 rounded-r bg-gray-50 dark:bg-gray-600 dark:text-gray-300 w-1/3 flex items-center justify-center">
                                                kWh
                                            </span>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Jaarlijks elektriciteitsverbruik voor deze meter
                                        </p>
                                    </div>
                                    @endif

                                    @if($meter->measures_gas)
                                    <div class="mb-6">
                                        <label for="gas_input_{{ $meter->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Gas
                                            <span class="sr-only">(in kubieke meter)</span>
                                        </label>
                                        <div class="flex">
                                            <input 
                                                type="number" 
                                                step="0.1" 
                                                name="meters[{{ $meter->id }}][gas_target_m3]" 
                                                id="gas_input_{{ $meter->id }}" 
                                                class="p-2 border dark:border-gray-600 rounded-l w-2/3 dark:bg-gray-700 dark:text-gray-300"
                                                value="{{ $meterBudget->gas_target_m3 ?? 600 }}"
                                                required
                                            >
                                            <span class="p-2 border dark:border-gray-600 border-l-0 rounded-r bg-gray-50 dark:bg-gray-600 dark:text-gray-300 w-1/3 flex items-center justify-center">
                                                m³
                                            </span>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Jaarlijks gasverbruik voor deze meter
                                        </p>
                                    </div>
                                    @endif
                                </section>

                                <!-- Monthly Budget Section -->
                                <section class="md:col-span-2" aria-labelledby="monthly-budget-heading-{{ $meter->id }}">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 id="monthly-budget-heading-{{ $meter->id }}" class="font-semibold text-lg dark:text-gray-200">
                                            {{ __('Maandelijks budget') }}
                                        </h3>
                                        
                                        @if($meter->measures_electricity && $meter->measures_gas)
                                        <div class="relative">
                                            <button 
                                                type="button" 
                                                class="utility-toggle-btn px-4 py-2 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-md shadow-sm w-40 text-center font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                data-meter-id="{{ $meter->id }}"
                                            >
                                                <span class="active-utility-text text-sm font-medium dark:text-gray-200">kWh</span>
                                            </button>
                                        </div>
                                        @endif
                                    </div>

                                    <!-- Budget Progress -->
                                    <div class="mb-4" role="status" aria-live="polite">
                                        <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-1">
                                            <span>Jaarlijks budget: <span class="yearly-total">{{ $meter->measures_electricity ? ($meterBudget->electricity_target_kwh ?? 1750) : ($meterBudget->gas_target_m3 ?? 600) }}</span> <span class="total-unit">{{ $meter->measures_electricity ? 'kWh' : 'm³' }}</span></span>
                                            <span>Gebruikt: <span class="used-total">{{ $meter->measures_electricity ? ($meterBudget->electricity_target_kwh ?? 1750) : ($meterBudget->gas_target_m3 ?? 600) }}</span> <span class="used-unit">{{ $meter->measures_electricity ? 'kWh' : 'm³' }}</span> (<span class="used-percentage">100</span>%)</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-1">
                                            <div class="budget-progress-bar bg-blue-600 h-2.5 rounded-full" style="width: 100%"></div>
                                        </div>
                                        <div class="budget-warning text-sm text-yellow-500 dark:text-yellow-400 hidden" aria-live="assertive">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            Maandelijkse waarden mogen niet meer dan het jaarbudget zijn.
                                        </div>
                                    </div>

                                    <!-- Monthly Sliders -->
                                    <div class="monthly-sliders-container" data-meter-id="{{ $meter->id }}">
                                        <!-- Sliders will be populated by JavaScript -->
                                    </div>

                                    <!-- Hidden fields for monthly data -->
                                    <div class="monthly-budget-fields" data-meter-id="{{ $meter->id }}">
                                        <!-- Hidden fields will be populated by JavaScript -->
                                    </div>
                                </section>

                                <!-- Daily budget section -->
                                <section class="md:col-span-4 h-full" aria-labelledby="monthly-budget-heading-{{ $meter->id }}">
                                    <div class="flex flex-col h-full">
                                        <div class="mb-6">
                                            <h3 id="monthly-budget-heading-{{ $meter->id }}" class="font-semibold text-lg dark:text-gray-200 mb-4">
                                                {{ __('Dagelijkse budgetten') }}
                                            </h3>
                                            
                                            <!-- Month navigation -->
                                            <div class="flex flex-row gap-2">
                                                <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 rounded-md">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                                    </svg>                                        
                                                </button>
                                                <select class="month-select bg-gray-200 dark:bg-gray-700 dark:text-gray-200 rounded-md bg-none text-center px-4 py-2 border-0 min-w-32"
                                                        data-meter-id="{{ $meter->id }}" onchange="handleMonthChange(this)">
                                                    <option value="1">Januari</option>
                                                    <option value="2">Februari</option>
                                                    <option value="3">Maart</option> 
                                                </select>
                                                <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 rounded-md">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        @php 
                                            $year = 2025;
                                            $month = 1;
                                            $days_in_current_month = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
                                            $extra_days = $days_in_current_month - 28;
                                            $day_counter = 1;
                                            $budget_for_current_month = 1570;
                                            $budget_divided = round($budget_for_current_month / $days_in_current_month, 1);
                                        @endphp

                                        <div class="flex-1 flex flex-col gap-6">

                                        <!-- First 4 weeks + extra days grid -->
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 flex-1">
                                            @for($week = 1; $week <= 4; $week++)
                                                <div class="week-container bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                                    <div class="mb-4">
                                                        <h4 class="font-semibold dark:text-gray-200">Week {{ $week }}</h4>
                                                    </div>
                                                    <div class="grid grid-cols-7 gap-2">
                                                        @for($day = 1; $day <= 7; $day++)
                                                            @if($day_counter <= $days_in_current_month)
                                                                <div class="flex flex-col">
                                                                    <label for="day-{{ $day_counter }}" class="text-xs mb-1 dark:text-gray-300">
                                                                        {{ $day_counter < 10 ? '0' . $day_counter : $day_counter }}
                                                                    </label>
                                                                    <input 
                                                                        type="number" 
                                                                        step="0.1" 
                                                                        id="day-{{ $day_counter }}"
                                                                        class="px-2 py-2 text-center border rounded-md flex-1 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                                                        name="day-{{ $day_counter }}"
                                                                        value="{{ $budget_divided }}"
                                                                    />
                                                                </div>
                                                                @php $day_counter++; @endphp
                                                            @else
                                                                <div class="w-full"></div>
                                                            @endif
                                                        @endfor
                                                    </div>
                                                </div>
                                            @endfor

                                            {{-- Extra days section als 5e grid item --}}
                                            @if($extra_days > 0)
                                                <div class="week-container bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                                    <div class="mb-4">
                                                        <h4 class="font-semibold dark:text-gray-200">Overige dagen</h4>
                                                    </div>
                                                    <div class="grid grid-cols-7 gap-2">
                                                        @for($day = 1; $day <= $extra_days; $day++)
                                                            <div class="flex flex-col">
                                                                <label for="day-{{ $day_counter }}" class="text-xs mb-1 dark:text-gray-300">
                                                                    {{ $day_counter < 10 ? '0' . $day_counter : $day_counter }}
                                                                </label>
                                                                <input 
                                                                    type="number" 
                                                                    step="0.1" 
                                                                    id="day-{{ $day_counter }}"
                                                                    class="px-2 py-2 text-center border rounded-md flex-1 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                                                    name="day-{{ $day_counter }}"
                                                                    value="{{ $budget_divided }}"
                                                                />
                                                            </div>
                                                            @php $day_counter++; @endphp
                                                        @endfor
                                                        
                                                        {{-- Vul lege kolommen op om grid consistent te houden --}}
                                                        @for($empty = $extra_days + 1; $empty <= 7; $empty++)
                                                            <div class="w-full"></div>
                                                        @endfor
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </section>

                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex justify-end space-x-4">
                    <button 
                        type="button" 
                        id="resetAllButton"
                        class="px-6 py-2 bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Reset Alle Meters
                    </button>
                    
                    <button 
                        type="submit" 
                        class="px-6 py-2 bg-green-600 text-white rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        Alle Budgetten Opslaan
                    </button>
                </div>
            </form>
        @endif
    </div>

    <style>
        .vertical-slider-container {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px 0;
        }

        .vertical-slider {
            height: 100%;
            position: relative;
            width: 20px;
        }

        .range-vertical {
            transform: rotate(270deg);
            transform-origin: center;
            position: absolute;
            top: 50%;
            left: 50%;
            margin-left: -60px;
            margin-top: -10px;
            width: 120px;
            height: 20px;
            background: transparent;
        }

        .range-vertical:focus {
            outline: 2px solid #3B82F6;
            outline-offset: 2px;
        }

        .range-vertical::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #3B82F6;
            cursor: pointer;
        }

        .range-vertical::-moz-range-thumb {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #3B82F6;
            cursor: pointer;
            border: none;
        }

        .range-vertical::-webkit-slider-runnable-track {
            width: 100%;
            height: 8px;
            cursor: pointer;
            background: #E5E7EB;
            border-radius: 4px;
        }

        .range-vertical::-moz-range-track {
            width: 100%;
            height: 8px;
            cursor: pointer;
            background: #E5E7EB;
            border-radius: 4px;
        }

        .dark .range-vertical::-webkit-slider-runnable-track {
            background: #4B5563;
        }

        .dark .range-vertical::-moz-range-track {
            background: #4B5563;
        }

        .electricity .range-vertical::-webkit-slider-thumb {
            background: #3B82F6;
        }

        .electricity .range-vertical::-moz-range-thumb {
            background: #3B82F6;
        }

        .gas .range-vertical::-webkit-slider-thumb {
            background: #F59E0B;
        }

        .gas .range-vertical::-moz-range-thumb {
            background: #F59E0B;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'];
            const meterBudgetData = new Map();
            
            // Initialize budget data for each meter
            @foreach($smartMeters as $meter)
                @php
                    $meterBudget = $existingBudgets->where('smart_meter_id', $meter->id)->first();
                    $monthlyBudgets = $meterBudget ? $meterBudget->monthlyBudgets : collect();
                @endphp
                
                meterBudgetData.set('{{ $meter->id }}', {
                    measures_electricity: {{ $meter->measures_electricity ? 'true' : 'false' }},
                    measures_gas: {{ $meter->measures_gas ? 'true' : 'false' }},
                    activeUtility: '{{ $meter->measures_electricity ? 'electricity' : 'gas' }}',
                    electricity: {
                        yearly: {{ $meterBudget->electricity_target_kwh ?? 1750 }},
                        monthly: [
                            @if($monthlyBudgets->count() === 12)
                                @foreach($monthlyBudgets->sortBy('month') as $budget)
                                    {{ $budget->electricity_target_kwh }},
                                @endforeach
                            @else
                                @for($i = 0; $i < 12; $i++)
                                    {{ ($meterBudget->electricity_target_kwh ?? 1750) / 12 }},
                                @endfor
                            @endif
                        ]
                    },
                    gas: {
                        yearly: {{ $meterBudget->gas_target_m3 ?? 600 }},
                        monthly: [
                            @if($monthlyBudgets->count() === 12)
                                @foreach($monthlyBudgets->sortBy('month') as $budget)
                                    {{ $budget->gas_target_m3 }},
                                @endforeach
                            @else
                                @for($i = 0; $i < 12; $i++)
                                    {{ ($meterBudget->gas_target_m3 ?? 600) / 12 }},
                                @endfor
                            @endif
                        ]
                    }
                });
            @endforeach

            // Initialize UI for each meter
            meterBudgetData.forEach((data, meterId) => {
                renderMonthlySliders(meterId);
                updateBudgetDisplay(meterId);
                
                // Add event listeners for yearly budget changes
                if (data.measures_electricity) {
                    const electricityInput = document.getElementById(`electricity_input_${meterId}`);
                    electricityInput.addEventListener('change', () => updateYearlyBudget(meterId, 'electricity'));
                }
                
                if (data.measures_gas) {
                    const gasInput = document.getElementById(`gas_input_${meterId}`);
                    gasInput.addEventListener('change', () => updateYearlyBudget(meterId, 'gas'));
                }
                
                // Add utility toggle if meter measures both
                if (data.measures_electricity && data.measures_gas) {
                    const toggleBtn = document.querySelector(`.utility-toggle-btn[data-meter-id="${meterId}"]`);
                    toggleBtn.addEventListener('click', () => toggleUtility(meterId));
                }
            });

            // Form submission handler
            document.getElementById('meterBudgetsForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Add monthly budget data to form
                meterBudgetData.forEach((data, meterId) => {
                    const monthlyFieldsContainer = document.querySelector(`.monthly-budget-fields[data-meter-id="${meterId}"]`);
                    monthlyFieldsContainer.innerHTML = '';
                    
                    for (let i = 0; i < 12; i++) {
                        if (data.measures_electricity) {
                            const electricityInput = document.createElement('input');
                            electricityInput.type = 'hidden';
                            electricityInput.name = `meters[${meterId}][monthly][${i}][electricity_target_kwh]`;
                            electricityInput.value = data.electricity.monthly[i];
                            monthlyFieldsContainer.appendChild(electricityInput);
                        }
                        
                        if (data.measures_gas) {
                            const gasInput = document.createElement('input');
                            gasInput.type = 'hidden';
                            gasInput.name = `meters[${meterId}][monthly][${i}][gas_target_m3]`;
                            gasInput.value = data.gas.monthly[i];
                            monthlyFieldsContainer.appendChild(gasInput);
                        }
                        
                        const monthInput = document.createElement('input');
                        monthInput.type = 'hidden';
                        monthInput.name = `meters[${meterId}][monthly][${i}][month]`;
                        monthInput.value = i + 1;
                        monthlyFieldsContainer.appendChild(monthInput);
                    }
                });
                
                this.submit();
            });

            // Reset all button
            document.getElementById('resetAllButton').addEventListener('click', function() {
                if (confirm('Weet u zeker dat u alle budgetten wilt resetten?')) {
                    meterBudgetData.forEach((data, meterId) => {
                        // Reset to default values
                        if (data.measures_electricity) {
                            data.electricity.yearly = 1750;
                            data.electricity.monthly = Array(12).fill(1750 / 12);
                            document.getElementById(`electricity_input_${meterId}`).value = 1750;
                        }
                        
                        if (data.measures_gas) {
                            data.gas.yearly = 600;
                            data.gas.monthly = Array(12).fill(600 / 12);
                            document.getElementById(`gas_input_${meterId}`).value = 600;
                        }
                        
                        renderMonthlySliders(meterId);
                        updateBudgetDisplay(meterId);
                    });
                }
            });

            function toggleUtility(meterId) {
                const data = meterBudgetData.get(meterId);
                const currentUtility = data.activeUtility;
                const newUtility = currentUtility === 'electricity' ? 'gas' : 'electricity';
                
                data.activeUtility = newUtility;
                
                const toggleBtn = document.querySelector(`.utility-toggle-btn[data-meter-id="${meterId}"]`);
                const utilityText = toggleBtn.querySelector('.active-utility-text');
                utilityText.textContent = newUtility === 'electricity' ? 'kWh' : 'm³';
                
                renderMonthlySliders(meterId);
                updateBudgetDisplay(meterId);
            }

            function updateYearlyBudget(meterId, utility) {
                const data = meterBudgetData.get(meterId);
                const input = document.getElementById(`${utility}_input_${meterId}`);
                const newValue = parseFloat(input.value) || 0;
                
                data[utility].yearly = newValue;
                
                // If this is the active utility, update display
                if (data.activeUtility === utility) {
                    updateBudgetDisplay(meterId);
                    renderMonthlySliders(meterId);
                }
            }

            function renderMonthlySliders(meterId) {
                const data = meterBudgetData.get(meterId);
                const container = document.querySelector(`.monthly-sliders-container[data-meter-id="${meterId}"]`);
                const activeUtility = data.activeUtility;
                const yearlyValue = data[activeUtility].yearly;
                const monthlyValues = data[activeUtility].monthly;
                const unit = activeUtility === 'electricity' ? 'kWh' : 'm³';
                const maxSliderValue = Math.ceil(yearlyValue / 6); // Max value for sliders
                
                container.innerHTML = '';
                
                const slidersGrid = document.createElement('div');
                slidersGrid.className = 'grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4';
                
                months.forEach((month, index) => {
                    const monthDiv = document.createElement('div');
                    monthDiv.className = `bg-gray-50 dark:bg-gray-700 rounded-lg p-3 flex flex-col items-center ${activeUtility}`;
                    
                    // Month name
                    const monthName = document.createElement('span');
                    monthName.className = 'font-medium dark:text-white text-sm';
                    monthName.textContent = month;
                    monthDiv.appendChild(monthName);
                    
                    // Slider container
                    const sliderContainer = document.createElement('div');
                    sliderContainer.className = 'vertical-slider-container';
                    
                    const sliderDiv = document.createElement('div');
                    sliderDiv.className = 'vertical-slider';
                    
                    // Slider input
                    const slider = document.createElement('input');
                    slider.type = 'range';
                    slider.min = '0';
                    slider.max = maxSliderValue;
                    slider.step = '0.1';
                    slider.value = Math.min(monthlyValues[index], maxSliderValue);
                    slider.className = 'range-vertical';
                    
                    slider.addEventListener('input', function() {
                        handleSliderChange(meterId, index, parseFloat(this.value));
                    });
                    
                    sliderDiv.appendChild(slider);
                    sliderContainer.appendChild(sliderDiv);
                    monthDiv.appendChild(sliderContainer);
                    
                    // Value input
                    const valueInput = document.createElement('input');
                    valueInput.type = 'number';
                    valueInput.min = '0';
                    valueInput.step = '0.1';
                    valueInput.value = monthlyValues[index].toFixed(1);
                    valueInput.className = 'text-sm dark:text-gray-300 mt-1 p-1 border dark:border-gray-600 rounded w-full text-center dark:bg-gray-700';
                    
                    valueInput.addEventListener('change', function() {
                        const newValue = parseFloat(this.value) || 0;
                        handleSliderChange(meterId, index, newValue);
                        slider.value = Math.min(newValue, maxSliderValue);
                    });
                    
                    monthDiv.appendChild(valueInput);
                    
                    // Unit label
                    const unitLabel = document.createElement('span');
                    unitLabel.className = 'text-sm dark:text-gray-300 mt-1';
                    unitLabel.textContent = unit;
                    monthDiv.appendChild(unitLabel);
                    
                    slidersGrid.appendChild(monthDiv);
                });
                
                container.appendChild(slidersGrid);
            }

            function handleSliderChange(meterId, monthIndex, newValue) {
                const data = meterBudgetData.get(meterId);
                const activeUtility = data.activeUtility;
                const yearlyValue = data[activeUtility].yearly;
                const currentTotal = data[activeUtility].monthly.reduce((sum, val) => sum + val, 0);
                const currentValue = data[activeUtility].monthly[monthIndex];
                const proposedTotal = currentTotal - currentValue + newValue;
                
                if (proposedTotal > yearlyValue) {
                    const maxAllowed = yearlyValue - (currentTotal - currentValue);
                    data[activeUtility].monthly[monthIndex] = maxAllowed;
                    
                    // Show warning
                    const warningEl = document.querySelector(`.meter-budget-section[data-meter-id="${meterId}"] .budget-warning`);
                    warningEl.classList.remove('hidden');
                    
                    // Update input field
                    const container = document.querySelector(`.monthly-sliders-container[data-meter-id="${meterId}"]`);
                    const valueInput = container.querySelectorAll('input[type="number"]')[monthIndex];
                    valueInput.value = maxAllowed.toFixed(1);
                } else {
                    data[activeUtility].monthly[monthIndex] = newValue;
                    
                    // Hide warning
                    const warningEl = document.querySelector(`.meter-budget-section[data-meter-id="${meterId}"] .budget-warning`);
                    warningEl.classList.add('hidden');
                }
                
                updateBudgetDisplay(meterId);
            }

            function updateBudgetDisplay(meterId) {
                const data = meterBudgetData.get(meterId);
                const activeUtility = data.activeUtility;
                const yearlyValue = data[activeUtility].yearly;
                const usedValue = data[activeUtility].monthly.reduce((sum, val) => sum + val, 0);
                const percentage = (usedValue / yearlyValue) * 100;
                
                const section = document.querySelector(`.meter-budget-section[data-meter-id="${meterId}"]`);
                const yearlyTotal = section.querySelector('.yearly-total');
                const usedTotal = section.querySelector('.used-total');
                const usedPercentage = section.querySelector('.used-percentage');
                const progressBar = section.querySelector('.budget-progress-bar');
                const totalUnit = section.querySelector('.total-unit');
                const usedUnit = section.querySelector('.used-unit');
                
                yearlyTotal.textContent = yearlyValue.toFixed(0);
                usedTotal.textContent = usedValue.toFixed(0);
                usedPercentage.textContent = percentage.toFixed(0);
                progressBar.style.width = `${Math.min(percentage, 100)}%`;
                
                const unit = activeUtility === 'electricity' ? 'kWh' : 'm³';
                totalUnit.textContent = unit;
                usedUnit.textContent = unit;
                
                // Update progress bar color
                progressBar.classList.remove('bg-red-500', 'bg-yellow-500', 'bg-blue-600');
                if (percentage > 95) {
                    progressBar.classList.add('bg-red-500');
                } else if (percentage > 80) {
                    progressBar.classList.add('bg-yellow-500');
                } else {
                    progressBar.classList.add('bg-blue-600');
                }
            }
        });
    </script>
</x-app-layout>