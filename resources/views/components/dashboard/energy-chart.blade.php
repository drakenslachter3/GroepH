@props(['type', 'title', 'buttonLabel', 'buttonColor', 'chartData', 'period'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
    <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
        <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-semibold">{{ $title }}</h3>
            <div>
                <button id="toggle{{ ucfirst($type) }}Comparison{{ $loop->index ?? 0 }}" class="text-sm px-3 py-1 bg-{{ $buttonColor }}-100 text-{{ $buttonColor }}-700 rounded hover:bg-{{ $buttonColor }}-200">
                    {{ $buttonLabel }}
                </button>
            </div>
        </div>
        <div style="height: 300px;">
            <canvas id="{{ $type }}Chart{{ $loop->index ?? 0 }}"></canvas>
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
                        backgroundColor: 'rgba({{ $type === "electricity" ? "59, 130, 246" : "245, 158, 11" }}, 0.5)',
                        borderColor: 'rgb({{ $type === "electricity" ? "59, 130, 246" : "245, 158, 11" }})',
                        borderWidth: 1
                    },
                    {
                        label: 'Target',
                        data: (chartData{{ ucfirst($type) }}.{{ $type }} && chartData{{ ucfirst($type) }}.{{ $type }}.target) || [],
                        type: 'line',
                        borderColor: 'rgb(220, 38, 38)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
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
                            color: textColor
                        },
                        grid: {
                            color: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
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
                            color: textColor
                        },
                        grid: {
                            color: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const dataIndex = context[0].dataIndex;
                                const value = chartData{{ ucfirst($type) }}.{{ $type }}.data[dataIndex] || 0;
                                const target = chartData{{ ucfirst($type) }}.{{ $type }}.target[dataIndex] || 0;
                                const percentage = target ? (value / target * 100).toFixed(1) : 0;
                                return `${percentage}% van je target\nKosten: €${(value * {{ $type === "electricity" ? 0.35 : 1.45 }}).toFixed(2)}`;
                            }
                        }
                    },
                    legend: {
                        labels: {
                            color: textColor
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
                    button.classList.remove('bg-{{ $buttonColor }}-200');
                    button.classList.add('bg-{{ $buttonColor }}-100');
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
                        backgroundColor: 'rgba(107, 114, 128, 0.5)',
                        borderColor: 'rgb(107, 114, 128)',
                        borderWidth: 1
                    });
                    button.textContent = 'Verberg Vorig Jaar';
                    button.classList.remove('bg-{{ $buttonColor }}-100');
                    button.classList.add('bg-{{ $buttonColor }}-200');
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
                        
                        {{ $type }}Chart.options.scales.x.title.color = newTextColor;
                        {{ $type }}Chart.options.scales.x.ticks.color = newTextColor;
                        {{ $type }}Chart.options.scales.x.grid.color = newGridColor;
                        {{ $type }}Chart.options.scales.y.title.color = newTextColor;
                        {{ $type }}Chart.options.scales.y.ticks.color = newTextColor;
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