@props(['type', 'title', 'buttonLabel', 'buttonColor', 'chartData', 'period', 'date' => null])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
    <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
        <!-- Verbeterde header sectie met datum weergave en tijdsinterval visualisatie -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
            <div class="flex flex-col mb-2 sm:mb-0">
                <h3 class="text-lg font-semibold dark:text-white">{{ $title }}</h3>
                
                <!-- Datum weergave -->
                <div class="mt-1 text-sm text-sky-600 dark:text-sky-300 font-medium">
                    @switch($period)
                        @case('day')
                            {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
                            @break
                        @case('month')
                            {{ \Carbon\Carbon::parse($date)->format('F Y') }}
                            @break
                        @case('year')
                            {{ \Carbon\Carbon::parse($date)->format('Y') }}
                            @break
                    @endswitch
                </div>
            </div>
            
            <!-- Periode keuze tabs -->
            <div class="flex w-full sm:w-auto mt-2 sm:mt-0 dark:border dark:border-gray-700">
                <a href="{{ route('dashboard', ['period' => 'day', 'date' => $date, 'housing_type' => request('housing_type', 'tussenwoning')]) }}" 
                   class="px-3 py-1 text-sm rounded-l-md {{ $period === 'day' ? 'bg-' . $buttonColor . '-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}">
                    Dag
                </a>
                <a href="{{ route('dashboard', ['period' => 'month', 'date' => $date, 'housing_type' => request('housing_type', 'tussenwoning')]) }}" 
                   class="px-3 py-1 text-sm {{ $period === 'month' ? 'bg-' . $buttonColor . '-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}">
                    Maand
                </a>
                <a href="{{ route('dashboard', ['period' => 'year', 'date' => $date, 'housing_type' => request('housing_type', 'tussenwoning')]) }}" 
                   class="px-3 py-1 text-sm rounded-r-md {{ $period === 'year' ? 'bg-' . $buttonColor . '-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}">
                    Jaar
                </a>
            </div>
        </div>
        
        <!-- Navigatie knoppen voor datum -->
        <div class="flex justify-between items-center mb-4">
            <a href="{{ route('dashboard', [
                'period' => $period, 
                'date' => \Carbon\Carbon::parse($date)->sub(1, $period)->format('Y-m-d'),
                'housing_type' => request('housing_type', 'tussenwoning')
            ]) }}" 
               class="p-1 text-gray-500 hover:text-{{ $buttonColor }}-500 dark:text-gray-400 dark:hover:text-{{ $buttonColor }}-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </a>
            
            <!-- Verbruik label -->
            <span class="px-3 py-1 text-sm bg-{{ $buttonColor }}-500 text-white dark:bg-{{ $buttonColor }}-600 dark:text-white rounded-md">
                {{ $type === "electricity" ? "kWh" : "m³" }} Verbruik
            </span>
            
            <a href="{{ route('dashboard', [
                'period' => $period, 
                'date' => \Carbon\Carbon::parse($date)->add(1, $period)->format('Y-m-d'),
                'housing_type' => request('housing_type', 'tussenwoning')
            ]) }}" 
               class="p-1 text-gray-500 hover:text-{{ $buttonColor }}-500 dark:text-gray-400 dark:hover:text-{{ $buttonColor }}-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
        
        <div class="relative" style="height: 300px;">
            <canvas id="{{ $type }}Chart{{ $loop->index ?? 0 }}"></canvas>
        </div>
        
        <div class="mt-4 flex justify-end">
            <button id="toggle{{ ucfirst($type) }}Comparison{{ $loop->index ?? 0 }}" class="text-sm px-3 py-1 bg-{{ $buttonColor }}-100 text-{{ $buttonColor }}-700 rounded hover:bg-{{ $buttonColor }}-200 dark:bg-{{ $buttonColor }}-800 dark:text-{{ $buttonColor }}-100 dark:hover:bg-{{ $buttonColor }}-700">
                {{ $buttonLabel }}
            </button>
        </div>
    </div>
</div>

@push('chart-scripts')
<script>
    // Debug info to console
    console.log('Chart Component Loaded: {{ $type }}');
    console.log('Chart Data:', @json($chartData ?? []));
    
    // Ensure chartData exists with proper structure
    const chartData{{ ucfirst($type) }} = @json($chartData ?? [
        'labels' => [],
        $type => ['data' => [], 'target' => []]
    ]);
    
    // Deze script zal worden uitgevoerd nadat de chart.js library is geladen
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            return;
        }
        
        // Check if dark mode is active
        const isDarkMode = document.documentElement.classList.contains('dark') || 
                          document.querySelector('html').classList.contains('dark') ||
                          window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Set the text color based on dark mode
        const textColor = isDarkMode ? '#FFFFFF' : '#000000';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        
        console.log('Initializing {{ $type }} chart');
        const {{ $type }}Ctx = document.getElementById('{{ $type }}Chart{{ $loop->index ?? 0 }}');
        
        if (!{{ $type }}Ctx) {
            console.error('Canvas element not found: {{ $type }}Chart{{ $loop->index ?? 0 }}');
            return;
        }
        
        const {{ $type }}Chart = new Chart({{ $type }}Ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData{{ ucfirst($type) }}.labels || [],
                datasets: [
                    {
                        label: '{{ $type === "electricity" ? "kWh" : "m³" }} Verbruik',
                        data: (chartData{{ ucfirst($type) }}.{{ $type }} && chartData{{ ucfirst($type) }}.{{ $type }}.data) || [],
                        backgroundColor: '{{ $type === "electricity" ? "rgba(59, 130, 246, 0.6)" : "rgba(245, 158, 11, 0.6)" }}',
                        borderColor: '{{ $type === "electricity" ? "rgb(37, 99, 235)" : "rgb(217, 119, 6)" }}',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: periodLabels['{{ $period }}'] || '{{ $period }}',
                            color: textColor
                        },
                        ticks: {
                            color: isDarkMode ? '#9CA3AF' : '#4B5563' // Lichtere tekst in donkere modus
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '{{ $type === "electricity" ? "Elektriciteit (kWh)" : "Gas (m³)" }}',
                            color: textColor
                        },
                        ticks: {
                            color: isDarkMode ? '#9CA3AF' : '#4B5563' // Lichtere tekst in donkere modus
                        },
                        grid: {
                            color: gridColor
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    tooltip: {
                        backgroundColor: isDarkMode ? 'rgba(17, 24, 39, 0.9)' : 'rgba(255, 255, 255, 0.9)',
                        titleColor: isDarkMode ? '#E5E7EB' : '#1F2937',
                        bodyColor: isDarkMode ? '#9CA3AF' : '#4B5563',
                        borderColor: isDarkMode ? '#374151' : '#E5E7EB',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            afterBody: function(context) {
                                const dataIndex = context[0].dataIndex;
                                const value = chartData{{ ucfirst($type) }}.{{ $type }}.data[dataIndex] || 0;
                                return ``;
                            }
                        }
                    },
                    legend: {
                        labels: {
                            color: textColor,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
        
        // Toggle vergelijking met vorig jaar
        const toggleButton = document.getElementById('toggle{{ ucfirst($type) }}Comparison{{ $loop->index ?? 0 }}');
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                const button = this;
                const dataset = {{ $type }}Chart.data.datasets.find(ds => ds.label === 'Vorig Jaar');
                
                if (dataset) {
                    // Verwijder de dataset als deze al bestaat
                    {{ $type }}Chart.data.datasets = {{ $type }}Chart.data.datasets.filter(ds => ds.label !== 'Vorig Jaar');
                    button.textContent = '{{ $buttonLabel }}';
                    button.classList.remove('bg-{{ $buttonColor }}-200', 'dark:bg-{{ $buttonColor }}-700');
                    button.classList.add('bg-{{ $buttonColor }}-100', 'dark:bg-{{ $buttonColor }}-800');
                } else {
                    // Check if lastYearData exists
                    if (!window.lastYearData || !window.lastYearData.{{ $type }}) {
                        console.error('Last year data is not defined');
                        return;
                    }
                    
                    // Voeg de dataset toe
                    {{ $type }}Chart.data.datasets.push({
                        label: 'Vorig Jaar',
                        data: window.lastYearData.{{ $type }},
                        backgroundColor: isDarkMode ? 'rgba(156, 163, 175, 0.5)' : 'rgba(107, 114, 128, 0.5)',
                        borderColor: isDarkMode ? 'rgb(156, 163, 175)' : 'rgb(107, 114, 128)',
                        borderWidth: 1
                    });
                    button.textContent = 'Verberg Vorig Jaar';
                    button.classList.remove('bg-{{ $buttonColor }}-100', 'dark:bg-{{ $buttonColor }}-800');
                    button.classList.add('bg-{{ $buttonColor }}-200', 'dark:bg-{{ $buttonColor }}-700');
                }
                
                {{ $type }}Chart.update();
            });
        } else {
            console.error('Toggle button not found');
        }
        
        // Add listener for dark mode changes (if using a theme toggle)
        const darkModeObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const isDarkNow = document.documentElement.classList.contains('dark') || 
                                    document.querySelector('html').classList.contains('dark');
                    if (isDarkNow !== isDarkMode) {
                        // Update chart colors
                        const newTextColor = isDarkNow ? '#FFFFFF' : '#000000';
                        const newGridColor = isDarkNow ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
                        const newTickColor = isDarkNow ? '#9CA3AF' : '#4B5563';
                        
                        {{ $type }}Chart.options.scales.x.title.color = newTextColor;
                        {{ $type }}Chart.options.scales.x.ticks.color = newTickColor;
                        {{ $type }}Chart.options.scales.x.grid.color = newGridColor;
                        {{ $type }}Chart.options.scales.y.title.color = newTextColor;
                        {{ $type }}Chart.options.scales.y.ticks.color = newTickColor;
                        {{ $type }}Chart.options.scales.y.grid.color = newGridColor;
                        {{ $type }}Chart.options.plugins.legend.labels.color = newTextColor;
                        
                        {{ $type }}Chart.update();
                    }
                }
            });
        });
        
        // Start observing html or document element for dark mode changes
        darkModeObserver.observe(document.documentElement, { attributes: true });
    });
</script>
@endpush