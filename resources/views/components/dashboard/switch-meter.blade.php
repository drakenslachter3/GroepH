@props(['title', 'meters', 'selectedMeterId'])

<section class="w-full p-2" aria-labelledby="switch-widget-title">
    <div class="flex flex-col">
        <div class="flex flex-row justify-between items-center w-full">
            <x-dashboard.widget-navigation :showPrevious="true" />
            <x-dashboard.widget-heading :title="$title" />
            <x-dashboard.widget-navigation :showNext="true" />
            
            <div class="tooltip relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 cursor-pointer hover:text-gray-600 dark:text-gray-300 dark:hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="tooltiptext invisible absolute z-10 px-3 py-2 text-sm bg-gray-800 text-white rounded shadow-lg -right-4 -bottom-20 w-48">
                    Selecteer een meter uit de lijst. Het dashboard wordt dan automatisch bijgewerkt met de bijbehorende gegevens.
                </span>
            </div>
        </div>

        <div class="mt-5">
            <label for="meter-search" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                Meterlijst
            </label>
            
            <div class="meter-selector-container">
                <!-- Search Input -->
                <div class="relative">
                    <input 
                        type="text" 
                        id="meter-search-input"
                        class="w-full rounded px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Zoek meter..."
                        autocomplete="off"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                
                <!-- Results Container -->
                <div id="meter-results" class="hidden absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg rounded-md border border-gray-200 dark:border-gray-600 max-h-60 overflow-auto">
                    <!-- Results will be populated by JavaScript -->
                </div>
                
                <!-- Selected Meter Display -->
                <div id="selected-meter-display" class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-md hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Geselecteerd:</span>
                            <span id="selected-meter-text" class="ml-2 text-sm text-gray-900 dark:text-white"></span>
                        </div>
                        <button type="button" id="clear-selection" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Form for submitting meter selection -->
                <form action="{{ route('dashboard.saveSelectedMeter') }}" method="POST" id="meter-form" class="mt-3 hidden">
                    @csrf
                    <input type="hidden" name="meter" id="selected-meter-id">
                    <button type="submit" class="w-full py-2 px-4 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md shadow-sm transition duration-200 focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Meter Activeren
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Meter data - passed from Blade template
    const meters = @json($meters);
    const selectedMeterId = {{ $selectedMeterId ?? 'null' }};
    
    // DOM elements
    const searchInput = document.getElementById('meter-search-input');
    const resultsContainer = document.getElementById('meter-results');
    const selectedDisplay = document.getElementById('selected-meter-display');
    const selectedText = document.getElementById('selected-meter-text');
    const selectedId = document.getElementById('selected-meter-id');
    const clearButton = document.getElementById('clear-selection');
    const form = document.getElementById('meter-form');
    
    let currentSelection = null;
    let isDropdownVisible = false;
    
    // Initialize with selected meter if any
    if (selectedMeterId) {
        const meter = meters.find(m => m.id == selectedMeterId);
        if (meter) {
            setSelectedMeter(meter);
        }
    }
    
    // Search input events
    searchInput.addEventListener('input', handleSearch);
    searchInput.addEventListener('focus', handleFocus);
    searchInput.addEventListener('keydown', handleKeydown);
    
    // Clear selection
    clearButton.addEventListener('click', clearSelection);
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            hideDropdown();
        }
    });
    
    function handleSearch() {
        const query = searchInput.value.toLowerCase().trim();
        
        if (query.length === 0) {
            showAllMeters();
        } else {
            const filtered = meters.filter(meter => {
                const meterIdMatch = meter.meter_id.toLowerCase().includes(query);
                const nameMatch = meter.name && meter.name.toLowerCase().includes(query);
                const locationMatch = meter.location && meter.location.toLowerCase().includes(query);
                return meterIdMatch || nameMatch || locationMatch;
            });
            showFilteredMeters(filtered, query);
        }
        showDropdown();
    }
    
    function handleFocus() {
        if (meters.length > 0) {
            if (searchInput.value.trim() === '') {
                showAllMeters();
            }
            showDropdown();
        }
    }
    
    function handleKeydown(e) {
        if (!isDropdownVisible) return;
        
        const items = resultsContainer.querySelectorAll('.meter-item');
        const highlighted = resultsContainer.querySelector('.meter-item.highlighted');
        let currentIndex = highlighted ? Array.from(items).indexOf(highlighted) : -1;
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                currentIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                highlightItem(items[currentIndex]);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                currentIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                highlightItem(items[currentIndex]);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (highlighted) {
                    const meterId = highlighted.dataset.meterId;
                    const meter = meters.find(m => m.id == meterId);
                    if (meter) selectMeter(meter);
                }
                break;
                
            case 'Escape':
                hideDropdown();
                searchInput.blur();
                break;
        }
    }
    
    function showAllMeters() {
        showFilteredMeters(meters);
    }
    
    function showFilteredMeters(filteredMeters, query = '') {
        resultsContainer.innerHTML = '';
        
        if (filteredMeters.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'py-3 px-4 text-sm text-gray-500 dark:text-gray-400';
            noResults.textContent = query ? `Geen meters gevonden voor "${query}"` : 'Geen meters beschikbaar';
            resultsContainer.appendChild(noResults);
            return;
        }
        
        filteredMeters.forEach(meter => {
            const item = document.createElement('div');
            item.className = 'meter-item cursor-pointer py-3 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-150';
            item.dataset.meterId = meter.id;
            
            if (currentSelection && currentSelection.id === meter.id) {
                item.classList.add('bg-indigo-600', 'text-white');
            }
            
            const name = meter.name ? `${meter.name} - ` : '';
            const location = meter.location || 'Geen locatie';
            
            item.innerHTML = `
                <div class="flex flex-col">
                    <span class="font-medium">${meter.meter_id}</span>
                    <span class="text-sm opacity-75">${name}${location}</span>
                </div>
            `;
            
            item.addEventListener('click', () => selectMeter(meter));
            item.addEventListener('mouseenter', () => highlightItem(item));
            
            resultsContainer.appendChild(item);
        });
    }
    
    function highlightItem(item) {
        // Remove existing highlights
        resultsContainer.querySelectorAll('.meter-item').forEach(i => {
            i.classList.remove('highlighted', 'bg-gray-100', 'dark:bg-gray-600');
        });
        
        // Add highlight to current item
        if (item && !item.classList.contains('bg-indigo-600')) {
            item.classList.add('highlighted', 'bg-gray-100', 'dark:bg-gray-600');
        }
    }
    
    function selectMeter(meter) {
        setSelectedMeter(meter);
        hideDropdown();
        searchInput.value = getDisplayText(meter);
    }
    
    function setSelectedMeter(meter) {
        currentSelection = meter;
        selectedText.textContent = getDisplayText(meter);
        selectedId.value = meter.id;
        selectedDisplay.classList.remove('hidden');
        form.classList.remove('hidden');
    }
    
    function clearSelection() {
        currentSelection = null;
        searchInput.value = '';
        selectedDisplay.classList.add('hidden');
        form.classList.add('hidden');
        hideDropdown();
        searchInput.focus();
    }
    
    function getDisplayText(meter) {
        const name = meter.name ? `${meter.name} - ` : '';
        const location = meter.location || 'Geen locatie';
        return `${name}${meter.meter_id} (${location})`;
    }
    
    function showDropdown() {
        resultsContainer.classList.remove('hidden');
        isDropdownVisible = true;
        
        // Position the dropdown
        const rect = searchInput.getBoundingClientRect();
        resultsContainer.style.width = rect.width + 'px';
    }
    
    function hideDropdown() {
        resultsContainer.classList.add('hidden');
        isDropdownVisible = false;
        
        // Clear highlights
        resultsContainer.querySelectorAll('.meter-item').forEach(item => {
            item.classList.remove('highlighted', 'bg-gray-100', 'dark:bg-gray-600');
        });
    }
});
</script>

<style>
/* Tooltip styling */
.tooltip:hover .tooltiptext {
    visibility: visible;
}

.tooltiptext {
    position: absolute;
    z-index: 1000;
    bottom: 125%;
    left: 50%;
    margin-left: -60px;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    text-align: center;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 12px;
    line-height: 1.2;
}

.tooltiptext::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: rgba(0, 0, 0, 0.8) transparent transparent transparent;
}

/* Dropdown positioning fix */
.meter-selector-container {
    position: relative;
}

#meter-results {
    width: 100%;
}

/* Focus states */
.meter-item:focus {
    outline: 2px solid #6366f1;
    outline-offset: 2px;
}
</style>