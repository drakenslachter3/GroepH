@props(['type', 'title', 'buttonLabel', 'buttonColor', 'chartData', 'period', 'date' => null])

<section class="p-2">
    <!-- Verbeterde header sectie met datum weergave en tijdsinterval visualisatie -->
    <div class="flex flex-col sm:flex-row mb-4 justify-between items-start sm:items-center">
        <div class="flex flex-col mb-2 sm:mb-0">
            <h3 class="text-lg font-semibold dark:text-white">{{ $title }}</h3>
            
            <!-- Datum weergave -->
            <div class="text-sm font-medium text-sky-600 dark:text-sky-300">
                <?php
                    switch ($period) {
                        case 'day':
                            echo date('d M Y', strtotime($date));
                            break;
                        case 'month':
                            echo date('M Y', strtotime($date));
                            break;
                        case 'year':
                            echo date('Y', strtotime($date));
                            break;
                    }
                ?>
            </div>
        </div>
        
        <!-- Periode keuze tabs -->
        <div class="flex mt-2 sm:mt-0">
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
            class="p-1 text-gray-500 hover:text-{{ $buttonColor }}-500 dark:text-gray-400 dark:hover:text-{{ $buttonColor }}-400"
            aria-label="Vorige {{ $period === 'day' ? 'dag' : ($period === 'month' ? 'maand' : 'jaar') }}">
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
            class="p-1 text-gray-500 hover:text-{{ $buttonColor }}-500 dark:text-gray-400 dark:hover:text-{{ $buttonColor }}-400"
            aria-label="Volgende {{ $period === 'day' ? 'dag' : ($period === 'month' ? 'maand' : 'jaar') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
        </a>
    </div>
    
    <!-- ACCESSIBILITY: Screen reader only introduction -->
    <div class="sr-only" aria-live="polite">
        <p>Hieronder vindt u een grafiek en een toegankelijke tabel die het {{ $type === "electricity" ? "elektriciteit" : "gas" }}verbruik weergeeft voor
        <?php
            switch ($period) {
                case 'day':
                    echo 'de dag ' . date('d M Y', strtotime($date));
                    break;
                case 'month':
                    echo 'de maand ' . date('M Y', strtotime($date));
                    break;
                case 'year':
                    echo 'het jaar ' . date('Y', strtotime($date));
                    break;
            }
        ?>. 
        Gebruik tab om door de tabel te navigeren.</p>
    </div>
    
    <!-- Grafiek voor visuele gebruikers -->
    <div class="relative" style="height: 300px;" aria-hidden="true">
        <canvas id="{{ $type }}Chart{{ $loop->index ?? 0 }}"></canvas>
    </div>
    
    <!-- ACCESSIBILITY: Toegankelijke tabel voor schermlezers -->
    <div class="mt-6 mb-4">

    <!-- Toegankelijke beschrijving van data trends -->
        <div class="mt-4" tabindex="0">
            <h5 class="text-sm font-medium mb-1">Samenvatting:</h5>
            <p class="text-sm">
                <?php
$values = (array)($chartData[$type]['data'] ?? []);

if (count($values) > 0) {
    $total = array_sum($values);
    $average = count($values) ? $total / count($values) : 0;
    $max = max($values);
    $min = min($values);

    $maxIndex = array_search($max, $values);
    $minIndex = array_search($min, $values);

    $maxLabel = $chartData['labels'][$maxIndex] ?? '';
    $minLabel = $chartData['labels'][$minIndex] ?? '';

    $unit = $type === "electricity" ? "kWh" : "m³";
    $typeNL = $type === "electricity" ? "elektriciteit" : "gas";

    $formattedTotal = number_format($total, 2, ',', '');
    $formattedAverage = number_format($average, 2, ',', '');
    $formattedMax = number_format($max, 2, ',', '');
    $formattedMin = number_format($min, 2, ',', '');

    switch ($period) {
        case 'day':
            echo "Op " . date('d F Y', strtotime($date)) . " was het totale {$typeNL}verbruik {$formattedTotal} {$unit}. ";
            echo "Gemiddeld werd er per uur {$formattedAverage} {$unit} verbruikt. ";
            echo "Het hoogste verbruik was om {$maxLabel}:00 uur met {$formattedMax} {$unit}, en het laagste om {$minLabel}:00 uur met {$formattedMin} {$unit}.";
            break;

        case 'month':
            echo "In " . date('F Y', strtotime($date)) . " was het totale {$typeNL}verbruik {$formattedTotal} {$unit}. ";
            echo "Gemiddeld werd er per dag {$formattedAverage} {$unit} verbruikt. ";
            echo "Het hoogste verbruik was op {$maxLabel} " . date('M', strtotime($date)) . " met {$formattedMax} {$unit}, en het laagste op {$minLabel} " . date('M', strtotime($date)) . " met {$formattedMin} {$unit}.";
            break;

        case 'year':
            echo "In " . date('Y', strtotime($date)) . " was het totale {$typeNL}verbruik {$formattedTotal} {$unit}. ";
            echo "Gemiddeld werd er per maand {$formattedAverage} {$unit} verbruikt. ";
            echo "Het hoogste verbruik was in {$maxLabel} met {$formattedMax} {$unit}, en het laagste in {$minLabel} met {$formattedMin} {$unit}.";
            break;
    }
} else {
    echo "Er zijn geen gegevens beschikbaar voor deze periode.";
}
?>
            </p>
        </div>
        <!-- <h4 id="{{ $type }}TableCaption" class="text-md font-medium mb-2">
            {{ $title }} - Toegankelijke tabel ({{ $type === "electricity" ? "Elektriciteit (kWh)" : "Gas (m³)" }})
        </h4> -->
        
        <div class="overflow-x-auto">
            <table class="w-full border-collapse table-auto" 
                   aria-labelledby="{{ $type }}TableCaption">
                <!-- <thead>
                    <tr>
                        <th scope="col" class="border px-4 py-2 dark:border-gray-700">
                            <?php
                                switch ($period) {
                                    case 'day':
                                        echo 'Uur';
                                        break;
                                    case 'month':
                                        echo 'Dag';
                                        break;
                                    case 'year':
                                        echo 'Maand';
                                        break;
                                }
                            ?>
                        </th>
                        <th scope="col" class="border px-4 py-2 dark:border-gray-700">{{ $type === "electricity" ? "Elektriciteit (kWh)" : "Gas (m³)" }}</th>
                    </tr>
                </thead> -->
                <tbody>
                    <?php
                        $labels = (array)($chartData['labels'] ?? []);
                        foreach ($labels as $index => $label):
                            $value = isset($chartData[$type]['data'][$index]) ? $chartData[$type]['data'][$index] : 0;
                            $unit = $type === "electricity" ? "kWh" : "m³";

                            $dateFormat = '';
                            switch ($period) {
                                case 'day':
                                    $dateFormat = $label . ':00 uur';
                                    break;
                                case 'month':
                                    $baseDate = date('Y-m', strtotime($date));
                                    $fullDate = $baseDate . '-' . str_pad($label, 2, '0', STR_PAD_LEFT);
                                    $dateFormat = date('l j F Y', strtotime($fullDate));
                                    break;
                                case 'year':
                                    $dateFormat = $label . ' ' . date('Y', strtotime($date));
                                    break;
                            }
                        ?>
                        <tr>
                            <td scope="row" class="border dark:border-gray-700" tabindex="0">
                                <?= htmlspecialchars($dateFormat) ?> <?= number_format($value, 3, ',', ' ') ?> <?= htmlspecialchars($unit) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="row" class="border px-4 py-2 text-left dark:border-gray-700" tabindex="0">Totaal</th>
                        <td class="border px-4 py-2 text-right font-bold dark:border-gray-700" tabindex="0">
                            {{ array_sum((array)($chartData[$type]['data'] ?? [])) }} {{ $type === "electricity" ? "kWh" : "m³" }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        
    </div>
    
    <div class="mt-4 flex justify-end">
        <button id="toggle{{ ucfirst($type) }}Comparison{{ $loop->index ?? 0 }}" 
                class="text-sm px-3 py-1 bg-{{ $buttonColor }}-100 text-{{ $buttonColor }}-700 rounded hover:bg-{{ $buttonColor }}-200 dark:bg-{{ $buttonColor }}-800 dark:text-{{ $buttonColor }}-100 dark:hover:bg-{{ $buttonColor }}-700">
            {{ $buttonLabel }}
        </button>
    </div>
</section>

@push('chart-scripts')
<script>
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
                    
                    // Update the accessible table with last year data when shown
                    updateAccessibleComparisonTable(window.lastYearData.{{ $type }});
                }
                
                {{ $type }}Chart.update();
            });
        } else {
            console.error('Toggle button not found');
        }
        
        // Function to update the accessible table with comparison data
        function updateAccessibleComparisonTable(lastYearData) {
            // This functionality could be implemented to add or show last year data
            // in the accessible table as well, mirroring what happens in the chart
            console.log('Last year data for accessible table:', lastYearData);
            // Implementation would depend on your DOM structure and requirements
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