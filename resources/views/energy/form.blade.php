<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Stel je jaarlijkse budget in') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Left Column (Sections 1 & 2) -->
                <div class="md:col-span-1 space-y-6">
                    <!-- Section 1: Yearly Budget -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700 relative">
                            <h3 class="font-semibold text-lg mb-4 dark:text-gray-200">{{ __('Jaarbudget') }}</h3>

                            <!-- Yearly Budget Form -->
                            <form id="yearlyBudgetForm" method="POST" action="{{ route('budget.store') }}">
                                @csrf
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Elektriciteit
                                    </label>
                                    <div class="flex">
                                        <input type="number" step="0.01" name="electricity_value" id="electricity_input"
                                            class="p-2 border dark:border-gray-600 rounded-l w-2/3 dark:bg-gray-700 dark:text-gray-300"
                                            value="{{ $yearlyBudget->electricity_target_kwh ?? 3500 }}">
                                        <span
                                            class="p-2 border dark:border-gray-600 border-l-0 rounded-r bg-gray-50 dark:bg-gray-600 dark:text-gray-300 w-1/3 flex items-center justify-center">
                                            kWh
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Gas
                                    </label>
                                    <div class="flex">
                                        <input type="number" step="0.01" name="gas_value" id="gas_input"
                                            class="p-2 border dark:border-gray-600 rounded-l w-2/3 dark:bg-gray-700 dark:text-gray-300"
                                            value="{{ $yearlyBudget->gas_target_m3 ?? 1200 }}">
                                        <span
                                            class="p-2 border dark:border-gray-600 border-l-0 rounded-r bg-gray-50 dark:bg-gray-600 dark:text-gray-300 w-1/3 flex items-center justify-center">
                                            m³
                                        </span>
                                    </div>
                                </div>

                                <!-- Monthly budget data will be added here via JavaScript -->
                            </form>

                            <div class="absolute bottom-0 right-0 p-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">1.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Action Buttons -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 relative">
                            <div class="flex flex-col space-y-4">
                                <button type="button" id="resetButton"
                                    class="w-full px-4 py-2 bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm hover:bg-gray-300 dark:hover:bg-gray-600">
                                    Reset
                                </button>

                                <button type="submit" form="yearlyBudgetForm"
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded-md shadow-sm hover:bg-green-700">
                                    Opslaan
                                </button>
                            </div>
                            <div class="absolute bottom-0 right-0 p-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">2.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Monthly Budget (Right Panel) -->
                <div class="md:col-span-2">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg relative">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <!-- Monthly Budget Header -->
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-semibold text-lg dark:text-gray-200">{{ __('Maandelijks budget') }}</h3>
                                <div class="relative">
                                    <button type="button" id="utilityToggleButton"
                                        class="px-4 py-2 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-md shadow-sm w-40 text-center font-medium">
                                        <span id="activeUtilityText"
                                            class="text-sm font-medium dark:text-gray-200">kWh</span>
                                    </button>
                                    <div class="absolute top-0 right-0 mt-1 mr-1">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">4.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Slider Range Controls -->
                            <div class="mb-4 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span
                                            class="text-sm font-medium text-gray-700 dark:text-gray-300">Sliderweergave:
                                        </span>
                                        <select id="sliderRangeMode"
                                            class="text-sm border border-gray-300 dark:border-gray-600 rounded p-1 dark:bg-gray-700 dark:text-gray-300 ml-2 min-w-[10rem]">
                                            <option value="normal">Automatisch</option>
                                            <option value="balanced">Gebalanceerd</option>
                                            <option value="full">Volledig bereik</option>
                                        </select>
                                    </div>

                                    <div class="text-sm">
                                        <span class="text-blue-600 dark:text-blue-400 font-medium">Huidig bereik:
                                        </span>
                                        <span id="sliderRangeDisplay" class="dark:text-gray-200">0 - 500</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Budget Progress and Indicator -->
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-1">
                                    <span>Jaarlijks budget: <span id="yearlyTotal">3500</span> <span
                                            id="totalUnit">kWh</span></span>
                                    <span>Gebruikt: <span id="usedTotal">3500</span> <span id="usedUnit">kWh</span>
                                        (<span id="usedPercentage">100</span>%)</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-1">
                                    <div id="budgetProgressBar" class="bg-blue-600 h-2.5 rounded-full"
                                        style="width: 100%"></div>
                                </div>
                                <div id="budgetWarning" class="text-sm text-yellow-500 dark:text-yellow-400 hidden">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Maandelijkse waardes mogen niet meer dan het jaarbudget zijn.
                                </div>
                            </div>

                            <!-- Monthly Budget Sliders Grid with Vertical Sliders -->
                            <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6" id="monthlySliders">
                                <!-- Sliders will be generated dynamically by JavaScript -->
                            </div>

                            <div class="absolute bottom-0 right-0 p-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">3.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .vertical-slider-container {
            height: 150px;
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
            margin-left: -75px;
            margin-top: -10px;
            width: 150px;
            height: 20px;
            background: transparent;
        }

        /* Focus styling */
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

        /* Dark mode adjustments */
        .dark .range-vertical::-webkit-slider-runnable-track {
            background: #4B5563;
        }

        .dark .range-vertical::-moz-range-track {
            background: #4B5563;
        }

        /* Utility specific colors */
        .electricity .range-vertical::-webkit-slider-thumb {
            background: #3B82F6;
            /* Blue for electricity */
        }

        .electricity .range-vertical::-moz-range-thumb {
            background: #3B82F6;
            /* Blue for electricity */
        }

        .gas .range-vertical::-webkit-slider-thumb {
            background: #F59E0B;
            /* Yellow/Amber for gas */
        }

        .gas .range-vertical::-moz-range-thumb {
            background: #F59E0B;
            /* Yellow/Amber for gas */
        }

        /* Skip link styling */
        .skip-links a:focus {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 100;
        }

        /* Focus indicators */
        input:focus,
        button:focus,
        select:focus,
        textarea:focus,
        a:focus {
            outline: 2px solid #3B82F6;
            outline-offset: 2px;
        }

        .bg-red-500 {
            background-color: #EF4444;
            font-weight: bold;
        }

        .bg-amber-500 {
            background-color: #F59E0B;
            font-weight: bold;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }

        .sr-only.focus:not-sr-only:focus {
            position: absolute;
            width: auto;
            height: auto;
            padding: 0.5rem;
            margin: 0;
            overflow: visible;
            clip: auto;
            white-space: normal;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuration
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'];
            let activeUtility = 'electricity'; // 'electricity' or 'gas'
            let sliderRangeMode = 'normal'; // 'normal', 'balanced', or 'full'

            const initialData = {
                electricity: {
                    yearly: {{ $yearlyBudget->electricity_target_kwh ?? 3500 }},
                    // Initialize with evenly distributed values (yearly ÷ 12)
                    monthly: Array(12).fill().map(() => ({
                        value: {{ ($yearlyBudget->electricity_target_kwh ?? 3500) / 12 }},
                        locked: false
                    }))
                },
                gas: {
                    yearly: {{ $yearlyBudget->gas_target_m3 ?? 1200 }},
                    // Initialize with evenly distributed values (yearly ÷ 12)
                    monthly: Array(12).fill().map(() => ({
                        value: {{ ($yearlyBudget->gas_target_m3 ?? 1200) / 12 }},
                        locked: false
                    }))
                }
            };

            // Initialize budget data from existing monthly data if available
            const budgetData = JSON.parse(JSON.stringify(initialData)); // Deep clone
            @if ($monthlyBudgets && count($monthlyBudgets) === 12)
                @foreach ($monthlyBudgets as $index => $budget)
                    budgetData.electricity.monthly[{{ $index }}].value =
                        {{ $budget->electricity_target_kwh }};
                    budgetData.gas.monthly[{{ $index }}].value = {{ $budget->gas_target_m3 }};
                @endforeach
            @endif

            // DOM Elements
            const utilityToggleButton = document.getElementById('utilityToggleButton');
            const activeUtilityText = document.getElementById('activeUtilityText');
            const yearlyTotal = document.getElementById('yearlyTotal');
            const totalUnit = document.getElementById('totalUnit');
            const usedTotal = document.getElementById('usedTotal');
            const usedUnit = document.getElementById('usedUnit');
            const usedPercentage = document.getElementById('usedPercentage');
            const budgetProgressBar = document.getElementById('budgetProgressBar');
            const budgetWarning = document.getElementById('budgetWarning');
            const monthlySliders = document.getElementById('monthlySliders');
            const resetButton = document.getElementById('resetButton');
            const electricityInput = document.getElementById('electricity_input');
            const gasInput = document.getElementById('gas_input');
            const sliderRangeModeSelect = document.getElementById('sliderRangeMode');
            const sliderRangeDisplay = document.getElementById('sliderRangeDisplay');
            const yearlyBudgetForm = document.getElementById('yearlyBudgetForm');
            const monthlyBudgetFields = document.getElementById('monthly-budget-fields');

            // Initialize UI
            renderMonthlySliders();
            updateBudgetDisplay();

            // Event Listeners
            utilityToggleButton.addEventListener('click', toggleUtility);
            resetButton.addEventListener('click', resetAllData);
            electricityInput.addEventListener('change', updateElectricityYearly);
            gasInput.addEventListener('change', updateGasYearly);
            sliderRangeModeSelect.addEventListener('change', changeSliderRangeMode);

            document.addEventListener('keydown', handleKeyboardNavigation);

            // Update the form submission to include monthly budget data
            yearlyBudgetForm.addEventListener('submit', function(e) {
                // Prevent default form submission
                e.preventDefault();

                // Clear any existing monthly budget fields
                monthlyBudgetFields.innerHTML = '';

                // Add monthly budget data to the form before submission
                for (let i = 0; i < 12; i++) {
                    // Create hidden inputs for each month
                    const monthInput = document.createElement('input');
                    monthInput.type = 'hidden';
                    monthInput.name = `budgets[${i}][month]`;
                    monthInput.value = i + 1;
                    monthlyBudgetFields.appendChild(monthInput);

                    const electricityInput = document.createElement('input');
                    electricityInput.type = 'hidden';
                    electricityInput.name = `budgets[${i}][electricity_target_kwh]`;
                    electricityInput.value = budgetData.electricity.monthly[i].value;
                    monthlyBudgetFields.appendChild(electricityInput);

                    const gasInput = document.createElement('input');
                    gasInput.type = 'hidden';
                    gasInput.name = `budgets[${i}][gas_target_m3]`;
                    gasInput.value = budgetData.gas.monthly[i].value;
                    monthlyBudgetFields.appendChild(gasInput);
                }

                // Announce form submission for screen readers
                const statusAnnouncement = document.createElement('div');
                statusAnnouncement.setAttribute('aria-live', 'assertive');
                statusAnnouncement.classList.add('sr-only');
                statusAnnouncement.textContent = 'Formulier wordt verzonden. Even geduld alstublieft.';
                document.body.appendChild(statusAnnouncement);

                // Now submit the form
                yearlyBudgetForm.submit();
            });

            // keyboard navigation for accessibility
            function handleKeyboardNavigation(e) {
                const activeElement = document.activeElement;
                
                // Handle slider keyboard controls
                if (activeElement && activeElement.classList.contains('range-vertical')) {
                    const currentValue = parseFloat(activeElement.value);
                    const step = parseFloat(activeElement.step) || 1;
                    const max = parseFloat(activeElement.max);
                    const min = parseFloat(activeElement.min);
                    
                    if (e.key === 'ArrowUp' || e.key === 'ArrowRight') {
                        // Increase value
                        if (currentValue + step <= max) {
                            activeElement.value = currentValue + step;
                            const event = new Event('input', {
                                'bubbles': true,
                                'cancelable': true
                            });
                            activeElement.dispatchEvent(event);
                        }
                        e.preventDefault();
                    } else if (e.key === 'ArrowDown' || e.key === 'ArrowLeft') {
                        // Decrease value
                        if (currentValue - step >= min) {
                            activeElement.value = currentValue - step;
                            const event = new Event('input', {
                                'bubbles': true,
                                'cancelable': true
                            });
                            activeElement.dispatchEvent(event);
                        }
                        e.preventDefault();
                    }
                }
                
                // Handle utility toggle with keyboard
                if (activeElement === utilityToggleButton && e.key === ' ') {
                    toggleUtility();
                    e.preventDefault();
                }
            }

            // Functions
            function toggleUtility() {
                // Toggle between electricity and gas
                activeUtility = activeUtility === 'electricity' ? 'gas' : 'electricity';

                // Update ARIA for screen readers
                const newType = activeUtility === 'electricity' ? 'elektriciteit' : 'gas';
                utilityToggleButton.setAttribute('aria-label', `Wissel naar ${activeUtility === 'electricity' ? 'gas' : 'elektriciteit'}`);
                
                // Announce change to screen readers
                const announcement = document.createElement('div');
                announcement.classList.add('sr-only');
                announcement.setAttribute('aria-live', 'assertive');
                announcement.textContent = `Nu bezig met ${newType} instellingen.`;
                document.body.appendChild(announcement);
                
                // Update UI to reflect the active utility
                if (activeUtility === 'electricity') {
                    activeUtilityText.textContent = 'kWh';
                    totalUnit.textContent = 'kWh';
                    usedUnit.textContent = 'kWh';
                    budgetProgressBar.classList.remove('bg-yellow-500');
                    budgetProgressBar.classList.add('bg-blue-600');
                } else {
                    activeUtilityText.textContent = 'm³';
                    totalUnit.textContent = 'm³';
                    usedUnit.textContent = 'm³';
                    budgetProgressBar.classList.remove('bg-blue-600');
                    budgetProgressBar.classList.add('bg-yellow-500');
                }

                setTimeout(() => {
                    document.body.removeChild(announcement);
                }, 1000);

                // Re-render the sliders with the new active utility
                renderMonthlySliders();
                updateBudgetDisplay();
            }

            function changeSliderRangeMode() {
                sliderRangeMode = sliderRangeModeSelect.value;
                renderMonthlySliders();
                updateSliderRangeDisplay();

                // Announce change to screen readers
                const announcement = document.createElement('div');
                announcement.classList.add('sr-only');
                announcement.setAttribute('aria-live', 'polite');
                announcement.textContent = `Sliderweergave gewijzigd naar ${sliderRangeModeSelect.options[sliderRangeModeSelect.selectedIndex].text}.`;
                document.body.appendChild(announcement);
                
                setTimeout(() => {
                    document.body.removeChild(announcement);
                }, 1000);
            }

            function updateSliderRangeDisplay() {
                const maxValue = getSliderMaxValue();
                sliderRangeDisplay.textContent = `0 - ${maxValue}`;
            }

            function getSliderMaxValue() {
                const yearlyValue = budgetData[activeUtility].yearly;

                // Determine the appropriate max value based on the current mode
                switch (sliderRangeMode) {
                    case 'normal':
                        // Approximately double the average monthly value for normal distribution
                        return Math.ceil(yearlyValue / 6);
                    case 'balanced':
                        // About half the yearly value - allows significant variation but still usable
                        return Math.ceil(yearlyValue / 2);
                    case 'full':
                        // Full yearly budget - allows allocating everything to one month
                        return yearlyValue;
                    default:
                        return Math.ceil(yearlyValue / 6);
                }
            }

            function getSliderAppearanceValue(actualValue) {
                // This function maps the actual value to what is displayed on the slider
                // This way, we can have sliders with smaller ranges while still allowing the full range
                const maxValue = getSliderMaxValue();
                const yearlyValue = budgetData[activeUtility].yearly;

                if (actualValue > maxValue) {
                    // If value exceeds the slider's visual range, cap it for display purposes
                    return maxValue;
                }

                return actualValue;
            }

            function getActualValueFromSlider(sliderValue) {
                // Convert displayed slider value back to actual value if needed
                return parseFloat(sliderValue);
            }

            function renderMonthlySliders() {
                // Clear existing sliders
                monthlySliders.innerHTML = '';

                // Get current utility data
                const data = budgetData[activeUtility].monthly;
                const yearlyBudgetValue = budgetData[activeUtility].yearly;
                const unit = activeUtility === 'electricity' ? 'kWh' : 'm³';
                const maxSliderValue = getSliderMaxValue();

                // Update the slider range display
                updateSliderRangeDisplay();

                // Create sliders for each month
                months.forEach((month, index) => {
                    const monthlyValue = parseFloat(data[index].value.toFixed(1));
                    const sliderValue = getSliderAppearanceValue(monthlyValue);
                    const isLocked = data[index].locked;

                    // Create month container
                    const monthDiv = document.createElement('div');
                    monthDiv.className = `bg-gray-50 dark:bg-gray-700 rounded-lg p-3 flex flex-col items-center ${activeUtility}`;
                    monthDiv.setAttribute('role', 'group');
                    monthDiv.setAttribute('aria-labelledby', `month-label-${index}`);

                    // Month name
                    const monthName = document.createElement('span');
                    monthName.className = 'font-medium dark:text-white text-sm';
                    monthName.id = `month-label-${index}`;
                    monthName.textContent = month;
                    monthDiv.appendChild(monthName);

                    // Vertical slider container
                    const sliderContainer = document.createElement('div');
                    sliderContainer.className = 'vertical-slider-container';

                    const sliderDiv = document.createElement('div');
                    sliderDiv.className = 'vertical-slider';

                    // Slider input with ARIA attributes
                    const slider = document.createElement('input');
                    slider.type = 'range';
                    slider.min = '0';
                    slider.max = maxSliderValue;
                    slider.step = '0.1';
                    slider.value = sliderValue;
                    slider.className = 'range-vertical';
                    slider.dataset.month = index;
                    slider.dataset.actualValue = monthlyValue;
                    slider.disabled = isLocked;
                    slider.id = `slider-${index}`;
                    
                    // ARIA attributes for accessibility
                    slider.setAttribute('aria-valuemin', '0');
                    slider.setAttribute('aria-valuemax', maxSliderValue);
                    slider.setAttribute('aria-valuenow', sliderValue);
                    slider.setAttribute('aria-valuetext', `${monthlyValue} ${unit}`);
                    slider.setAttribute('aria-labelledby', `month-label-${index}`);
                    slider.setAttribute('aria-describedby', `slider-desc-${index}`);

                    // Add event listener to update data when slider changes
                    slider.addEventListener('input', function() {
                        const actualValue = getActualValueFromSlider(this.value);
                        handleSliderChange(index, actualValue);
                        this.setAttribute('aria-valuenow', this.value);
                        this.setAttribute('aria-valuetext', `${data[index].value.toFixed(1)} ${unit}`);
                    });

                    sliderDiv.appendChild(slider);
                    sliderContainer.appendChild(sliderDiv);
                    monthDiv.appendChild(sliderContainer);

                    // Value display and input field for accessibility
                    const valueContainer = document.createElement('div');
                    valueContainer.className = 'mt-1 flex flex-col items-center w-full';
                    
                    // Slider description for screen readers
                    const sliderDesc = document.createElement('span');
                    sliderDesc.id = `slider-desc-${index}`;
                    sliderDesc.className = 'sr-only';
                    sliderDesc.textContent = `Gebruik pijltjestoetsen om het ${month} ${unit} budget aan te passen`;
                    valueContainer.appendChild(sliderDesc);
                    
                    // Add numeric input field for accessibility
                    const valueInput = document.createElement('input');
                    valueInput.type = 'number';
                    valueInput.min = '0';
                    valueInput.max = yearlyBudgetValue;
                    valueInput.step = '0.1';
                    valueInput.value = monthlyValue;
                    valueInput.className = 'text-sm dark:text-gray-300 mt-1 p-1 border dark:border-gray-600 rounded w-full text-center dark:bg-gray-700';
                    valueInput.disabled = isLocked;
                    valueInput.id = `input-${index}`;
                    valueInput.setAttribute('aria-label', `${month} budget in ${unit}`);
                    
                    // Add event listener to update when input changes
                    valueInput.addEventListener('change', function() {
                        const newValue = parseFloat(this.value) || 0;
                        handleSliderChange(index, newValue);
                        
                        // Also update slider position and ARIA
                        const slider = document.getElementById(`slider-${index}`);
                        if (slider) {
                            slider.value = getSliderAppearanceValue(newValue);
                            slider.setAttribute('aria-valuenow', slider.value);
                            slider.setAttribute('aria-valuetext', `${newValue.toFixed(1)} ${unit}`);
                        }
                    });
                    
                    // Add unit label below input
                    const unitLabel = document.createElement('span');
                    unitLabel.className = 'text-sm dark:text-gray-300 mt-1';
                    unitLabel.textContent = unit;
                    
                    valueContainer.appendChild(valueInput);
                    valueContainer.appendChild(unitLabel);
                    monthDiv.appendChild(valueContainer);

                    // Lock button with ARIA for accessibility
                    const lockButton = document.createElement('button');
                    lockButton.type = 'button';
                    lockButton.className = `w-6 h-6 rounded-md mt-2 flex items-center justify-center ${isLocked ? 'bg-gray-400 dark:bg-gray-500' : 'bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500'}`;
                    lockButton.setAttribute('aria-label', `${isLocked ? 'Ontgrendel' : 'Vergrendel'} ${month} waarde`);
                    lockButton.setAttribute('aria-pressed', isLocked ? 'true' : 'false');
                    
                    // Add lock/unlock icon for visual users
                    const lockIcon = document.createElement('span');
                    lockIcon.className = 'sr-only';
                    lockIcon.textContent = isLocked ? 'Vergrendeld' : 'Ontgrendeld';
                    lockButton.appendChild(lockIcon);
                    
                    // Add visual icon based on lock state
                    const visualIcon = document.createElement('svg');
                    visualIcon.setAttribute('aria-hidden', 'true');
                    visualIcon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                    visualIcon.setAttribute('viewBox', '0 0 20 20');
                    visualIcon.setAttribute('fill', 'currentColor');
                    visualIcon.className = 'w-4 h-4';
                    
                    const iconPath = document.createElement('path');
                    if (isLocked) {
                        iconPath.setAttribute('d', 'M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z');
                    } else {
                        iconPath.setAttribute('d', 'M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z');
                    }
                    
                    visualIcon.appendChild(iconPath);
                    lockButton.appendChild(visualIcon);
                    
                    lockButton.addEventListener('click', function() {
                        toggleLock(index);
                        // Announce change to screen readers
                        const newState = data[index].locked ? 'vergrendeld' : 'ontgrendeld';
                        this.setAttribute('aria-pressed', data[index].locked ? 'true' : 'false');
                        
                        const announcement = document.createElement('div');
                        announcement.classList.add('sr-only');
                        announcement.setAttribute('aria-live', 'assertive');
                        announcement.textContent = `${month} is nu ${newState}`;
                        document.body.appendChild(announcement);
                        
                        setTimeout(() => {
                            document.body.removeChild(announcement);
                        }, 1000);
                    });
                    
                    monthDiv.appendChild(lockButton);

                    // Add to grid
                    monthlySliders.appendChild(monthDiv);
                });
            }

            function handleSliderChange(monthIndex, newValue) {
                const data = budgetData[activeUtility].monthly;
                const yearlyBudgetValue = budgetData[activeUtility].yearly;
                const currentTotal = calculateTotalUsed();
                const currentValue = data[monthIndex].value;
                const proposedValue = parseFloat(newValue);
                const unit = activeUtility === 'electricity' ? 'kWh' : 'm³';

                // Calculate what the new total would be
                const proposedTotal = currentTotal - currentValue + proposedValue;

                // Check if the proposed change would exceed the yearly budget
                if (proposedTotal > yearlyBudgetValue) {
                    const maxAllowed = yearlyBudgetValue - (currentTotal - currentValue);

                    data[monthIndex].value = maxAllowed;

                    // Show the warning to all users
                    budgetWarning.classList.remove('hidden');

                    // Announce warning to screen readers
                    const warningAnnouncement = document.createElement('div');
                    warningAnnouncement.classList.add('sr-only');
                    warningAnnouncement.setAttribute('aria-live', 'assertive');
                    warningAnnouncement.textContent = `Waarschuwing: maximum budget bereikt. Waarde aangepast naar ${maxAllowed.toFixed(1)} ${unit}`;
                    document.body.appendChild(warningAnnouncement);

                    const valueInput = document.getElementById(`input-${monthIndex}`);
                    if (valueInput) {
                        valueInput.value = maxAllowed.toFixed(1);
                    }

                    const slider = document.getElementById(`slider-${monthIndex}`);
                    if (slider) {
                        const displayValue = getSliderAppearanceValue(maxAllowed);
                        slider.value = displayValue;
                        slider.dataset.actualValue = maxAllowed;
                        slider.setAttribute('aria-valuenow', slider.value);
                        slider.setAttribute('aria-valuetext', `${maxAllowed.toFixed(1)} ${unit}`);
                    }
                    
                    setTimeout(() => {
                        document.body.removeChild(warningAnnouncement);
                    }, 5000);
                } else {
                    data[monthIndex].value = proposedValue;

                    // Hide the warning - we're within budget
                    budgetWarning.classList.add('hidden');

                    // Update the input value
                    const valueInput = document.getElementById(`input-${monthIndex}`);
                    if (valueInput) {
                        valueInput.value = proposedValue.toFixed(1);
                    }

                    // Update the slider's actual value data attribute
                    const slider = document.getElementById(`slider-${monthIndex}`);
                    if (slider) {
                        slider.dataset.actualValue = proposedValue;
                        slider.value = getSliderAppearanceValue(proposedValue);
                        slider.setAttribute('aria-valuenow', slider.value);
                        slider.setAttribute('aria-valuetext', `${proposedValue.toFixed(1)} ${unit}`);
                    }
                }

                // Update budget display
                updateBudgetDisplay();
            }

            function toggleLock(monthIndex) {
                // Toggle the lock state
                budgetData[activeUtility].monthly[monthIndex].locked = !budgetData[activeUtility].monthly[monthIndex].locked;

                // Re-render to update the UI
                renderMonthlySliders();
            }

            function updateBudgetDisplay() {
                const data = budgetData[activeUtility];
                const yearlyValue = data.yearly;
                const usedValue = calculateTotalUsed();
                const percentage = (usedValue / yearlyValue) * 100;

                // Update the text displays
                yearlyTotal.textContent = yearlyValue.toFixed(0);
                usedTotal.textContent = usedValue.toFixed(0);
                usedPercentage.textContent = percentage.toFixed(0);

                // Update progress bar
                budgetProgressBar.style.width = `${Math.min(percentage, 100)}%`;
                budgetProgressBar.setAttribute('aria-valuenow', Math.min(percentage, 100));

                // Change progress bar color based on percentage
                budgetProgressBar.classList.remove('bg-red-500', 'bg-yellow-500', 'bg-amber-500', 'bg-blue-600');
                
                if (percentage > 95) {
                    budgetProgressBar.classList.add('bg-red-500');
                } else if (percentage > 80) {
                    budgetProgressBar.classList.add('bg-amber-500');
                } else {
                    budgetProgressBar.classList.add('bg-blue-600');
                }
            }

            function calculateTotalUsed() {
                // Sum all monthly values
                return budgetData[activeUtility].monthly.reduce((sum, month) => sum + month.value, 0);
            }

            function resetAllData() {
                // Reset to initial data
                Object.assign(budgetData, JSON.parse(JSON.stringify(initialData)));

                // Reset input fields
                electricityInput.value = initialData.electricity.yearly;
                gasInput.value = initialData.gas.yearly;

                // Update UI
                renderMonthlySliders();
                updateBudgetDisplay();

                // Hide any warnings
                budgetWarning.classList.add('hidden');

                // Announce reset to screen readers
                const announcement = document.createElement('div');
                announcement.classList.add('sr-only');
                announcement.setAttribute('aria-live', 'assertive');
                announcement.textContent = 'Alle waarden zijn gereset naar standaardwaarden.';
                document.body.appendChild(announcement);
                
                // After a short delay, remove the announcement
                setTimeout(() => {
                    document.body.removeChild(announcement);
                }, 1000);
            }

            function updateElectricityYearly() {
                const newYearly = parseFloat(electricityInput.value) || initialData.electricity.yearly;
                budgetData.electricity.yearly = newYearly;

                // If the active utility is electricity, update the display
                if (activeUtility === 'electricity') {
                    updateBudgetDisplay();
                    renderMonthlySliders(); // Re-render to update max values on sliders
                }
            }

            function updateGasYearly() {
                const newYearly = parseFloat(gasInput.value) || initialData.gas.yearly;
                budgetData.gas.yearly = newYearly;

                // If the active utility is gas, update the display
                if (activeUtility === 'gas') {
                    updateBudgetDisplay();
                    renderMonthlySliders(); // Re-render to update max values on sliders
                }
            }

            // Handle alert message display and duration
            const successAlert = document.querySelector('.bg-green-100');
            const errorAlert = document.querySelector('.bg-red-100');

            if (successAlert || errorAlert) {
                setTimeout(() => {
                    if (successAlert) successAlert.style.display = 'none';
                    if (errorAlert) errorAlert.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</x-app-layout>