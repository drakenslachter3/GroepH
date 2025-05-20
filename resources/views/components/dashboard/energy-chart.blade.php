@props(['type', 'title', 'buttonLabel', 'buttonColor', 'chartData', 'period', 'date' => null])

@php
    use Carbon\Carbon;

    $currentDate = Carbon::parse($date);
    
    $formattedDate = $date;
    if ($period == 'month' && $date) {
        $dateObj = Carbon::parse($date);
        $formattedDate = $dateObj->format('Y-m');
        $daysInMonth = Carbon::createFromFormat('Y-m', $formattedDate)->daysInMonth;
    } else {
        $daysInMonth = 30;
    }

    $previousDate = match($period) {
        'day' => $currentDate->copy()->subDay(),
        'month' => $currentDate->copy()->subMonthNoOverflow(),
        'year' => $currentDate->copy()->subYear(),
        default => $currentDate
    };

    $nextDate = match($period) {
        'day' => $currentDate->copy()->addDay(),
        'month' => $currentDate->copy()->addMonthNoOverflow(),
        'year' => $currentDate->copy()->addYear(),
        default => $currentDate
    };
    
    $dataKey = $type === 'electricity' ? 'energy_consumed' : 'gas_delivered';
    $previousYearKey = $dataKey . '_previous_year';
    $unitLabel = $type === 'electricity' ? 'kWh' : 'm³';
    $backgroundColor = $type === 'electricity' ? 'rgba(59, 130, 246, 0.6)' : 'rgba(245, 158, 11, 0.6)';
    $borderColor = $type === 'electricity' ? 'rgb(37, 99, 235)' : 'rgb(217, 119, 6)';
@endphp

<section class="p-6" aria-labelledby="chart-widget-title">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
        <x-widget-navigation :showPrevious="true" />
        <x-widget-heading :title="$title" :type="$type" :date="$date" :period="$period" />
        <x-widget-navigation :showNext="true" />
        
        <div class="flex w-full sm:w-auto mt-2 sm:mt-0 overflow-hidden rounded-md">
            @foreach (['day' => 'Dag', 'month' => 'Maand', 'year' => 'Jaar'] as $key => $label)
                <a href="{{ route('dashboard', ['period' => $key, 'date' => $date, 'housing_type' => request('housing_type', 'tussenwoning')]) }}"
                class="px-3 py-1 text-sm transition-colors
                    {{ $loop->first ? 'rounded-l-md' : '' }}
                    {{ $loop->last ? 'rounded-r-md' : '' }}
                    {{ $period === $key 
                        ? 'bg-' . $buttonColor . '-500 text-white' 
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="flex justify-between items-center mb-4">
        {{-- Previous Button --}}
        <a href="{{ route('dashboard', [
            'period' => $period, 
            'date' => $previousDate->format('Y-m-d'),
            'housing_type' => request('housing_type', 'tussenwoning')
        ]) }}" 
        class="p-1 text-gray-500 hover:text-{{ $buttonColor }}-500 dark:text-gray-400 dark:hover:text-{{ $buttonColor }}-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
        </a>

        {{-- Label --}}
        <span class="px-3 py-1 text-sm bg-{{ $buttonColor }}-500 text-white dark:bg-{{ $buttonColor }}-600 dark:text-white rounded-md">
            {{ $unitLabel }} Verbruik
        </span>

        {{-- Next Button --}}
        <a href="{{ route('dashboard', [
            'period' => $period, 
            'date' => $nextDate->format('Y-m-d'),
            'housing_type' => request('housing_type', 'tussenwoning')
        ]) }}" 
        class="p-1 text-gray-500 hover:text-{{ $buttonColor }}-500 dark:text-gray-400 dark:hover:text-{{ $buttonColor }}-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
        </a>
    </div>
    
    <div class="relative" style="height: 300px;">
        <canvas id="{{ $type }}Chart"></canvas>
    </div>
    
    <div class="mt-4 flex justify-end">
        <button id="toggle{{ ucfirst($type) }}Comparison{{ $loop->index ?? 0 }}" class="text-sm px-3 py-1 bg-{{ $buttonColor }}-100 text-{{ $buttonColor }}-700 rounded hover:bg-{{ $buttonColor }}-200 dark:bg-{{ $buttonColor }}-800 dark:text-{{ $buttonColor }}-100 dark:hover:bg-{{ $buttonColor }}-700">
            {{ $buttonLabel }}
        </button>
    </div>

    {{-- Accessible data table for the chart to support screen readers --}}
    <div class="focus:not-sr-only focus:absolute focus:z-10 focus:bg-white focus:dark:bg-gray-800 focus:p-4 focus:border focus:border-gray-300 focus:dark:border-gray-600 focus:shadow-lg focus:rounded-md focus:w-full focus:max-w-3xl">
        <div id="{{ $type }}TableCaption" class="text-lg font-semibold mb-2 dark:text-white" tabindex="0">
            @php
                $formattedPeriodDate = match($period) {
                    'day' => Carbon::parse($date)->translatedFormat('l j F Y'),
                    'month' => Carbon::parse($date)->translatedFormat('F Y'),
                    'year' => Carbon::parse($date)->translatedFormat('Y'),
                    default => ''
                };
            @endphp

            {{ $title }} - Overzicht verbruik voor {{ $formattedPeriodDate }}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse table-auto">
                <thead>
                    <tr>
                        <th scope="col" class="sr-only">Tijdsinterval</th>
                        <th scope="col" class="sr-only">Huidig verbruik</th>
                        @if(!empty($chartData[$dataKey . '_previous_year']))
                        <th scope="col" class="sr-only">Vorig jaar verbruik</th>
                        <th scope="col" class="sr-only">Verschil</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalCurrent = 0;
                        $totalPrevious = 0;
                        $currentData = $chartData[$dataKey] ?? [];
                        $previousData = empty($chartData[$dataKey . '_previous_year']) ?? [];
                        $hasPreviousYearData = true;
                        $currentDate = Carbon::parse($date);
                        $unit = $type === 'electricity' ? 'kWh' : 'm³';
                    @endphp

                    @foreach($currentData as $index => $value)
                        @php
                            $totalCurrent += $value;
                            $prevValue = $previousData[$index] ?? null;
                            if ($prevValue !== null) {
                                $totalPrevious += $prevValue;
                            }
                            $diff = $prevValue !== null ? $value - $prevValue : null;
                            $percentChange = $prevValue && $prevValue != 0 ? (($value - $prevValue) / $prevValue) * 100 : null;
                            
                            switch($period) {
                                case 'day':
                                    $hour = str_pad($index, 2);
                                    $nextHour = str_pad($index + 1, 2);
                                    $dateFormat = "Tussen {$hour} uur en {$nextHour} uur was je verbruik";
                                    break;

                                case 'month':
                                    $dayDate = $currentDate->copy()->setDay($index + 1);
                                    $dayNumber = $index + 1;
                                    $dayFormat = "{$dayNumber}e";

                                    $dateFormat = "Op {$dayFormat} was je verbruik";
                                    break;

                                case 'year':
                                    $months = [
                                        "Januari", "Februari", "Maart", "April", "Mei", "Juni", 
                                        "Juli", "Augustus", "September", "Oktober", "November", "December"
                                    ];
                                    $monthName = $months[$index] ?? "Maand " . ($index + 1);
                                    $dateFormat = "In {$monthName} was je verbruik";
                                    break;

                                default:
                                    $dateFormat = "Verbruik voor interval {$index}";
                                    break;
                            }
                        @endphp
                        <tr>
                            <td scope="row" class="border dark:border-gray-700" tabindex="0">
                                {{ $dateFormat }} {{ number_format($value, 2, ',', '.') }} {{ $unit }}.
                                @if($hasPreviousYearData && $prevValue !== null)
                                    Vorig jaar verbruikte je {{ number_format($prevValue, 2, ',', '.') }} {{ $unit }}.
                                    @if($diff !== null)
                                        {{ $diff < 0 ? 'Je bespaarde ' : 'Je verbruikte ' }}{{ number_format(abs($diff), 2, ',', '.') }} {{ $unit }} meer dan vorig jaar.
                                        {{-- ({{ $percentChange < 0 ? '-' : '+' }}{{ number_format(abs($percentChange), 1, ',', '.') }}%) --}}
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td scope="row" class="border font-bold dark:border-gray-700" tabindex="0">
                            In totaal verbruikte je {{ number_format($totalCurrent, 2, ',', '.') }} {{ $unit }}.
                            @if($hasPreviousYearData && $prevValue !== null)
                                @php
                                    $totalDiff = $totalCurrent - $totalPrevious;
                                    $totalPercentChange = $totalPrevious != 0 ? (($totalCurrent - $totalPrevious) / $totalPrevious) * 100 : null;
                                @endphp
                                Vorig jaar verbruikte je {{ number_format($totalPrevious, 2, ',', '.') }} {{ $unit }}.
                                {{ $totalDiff < 0 ? 'Je bespaarde' : 'Je verbruikte' }} {{ number_format(abs($totalDiff), 2, ',', '.') }} {{ $unit }} meer dan vorig jaar.
                                {{-- ({{ $totalPercentChange < 0 ? '-' : '+' }}{{ number_format(abs($totalPercentChange), 1, ',', '.') }}%) --}}
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button onclick="document.getElementById('{{ $type }}TableCaption')?.focus();">
            Ga naar de bovenkant van de {{ $title }} tabel
        </button>
    </div>
</section>

@push('chart-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add back to chart functionality
        const backButton = document.getElementById('back-to-chart-{{ $type }}');
        if (backButton) {
            backButton.addEventListener('click', function() {
                const header = document.getElementById('{{ $type }}TableHeader');
                if (header) {
                    header.setAttribute('tabindex', '-1'); // Make sure it can be focused
                    header.focus();
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            return;
        }

        const chartData = @json($chartData);

        let periodTranslated;
        const labels = [];
        
        // Generate labels based on period
        switch("{{ $period }}") {
            case 'day':
                // For day view - 24 hours (0-23)
                for (let i = 0; i < 24; i++) {
                    const hour = i.toString().padStart(2, '0');
                    labels.push(`${hour}:00`);
                }
                periodTranslated = 'Uren';
                break;
            case 'month':
                const daysInMonth = {{ $daysInMonth }};
                for (let i = 1; i <= daysInMonth; i++) {
                    labels.push(i.toString());
                }
                periodTranslated = 'Dagen';
                break;
            case 'year':
                labels.push("Januari", "Februari", "Maart", "April", "Mei", "Juni", 
                           "Juli", "Augustus", "September", "Oktober", "November", "December");
                periodTranslated = 'Maanden';
                break;
            default:
                console.error("Unknown period:", "{{ $period }}");
        }
        
        const chartCanvas = document.getElementById('{{ $type }}Chart');
        
        if (!chartCanvas) {
            console.error('Canvas element not found: {{ $type }}Chart');
            return;
        }
        
        // Get the correct data key based on the type
        const dataKey = "{{ $dataKey }}";
        const usageData = chartData[dataKey] || [];
        const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Use a more subtle axis color with opacity for better blending
        const axisColor = isDarkMode ? 'rgba(209, 213, 219, 0.3)' : 'rgba(75, 85, 99, 0.15)';
        const titleColor = isDarkMode ? '#F9FAFB' : '#000000';
        
        // Create chart instance as a variable to access it later for toggles
        const chart = new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '{{ $unitLabel }} Verbruik',
                    data: usageData,
                    backgroundColor: '{{ $backgroundColor }}',
                    borderColor: '{{ $borderColor }}',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: titleColor,
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: periodTranslated,
                            color: titleColor
                        },
                        ticks: {
                            color: titleColor,
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45,
                        },
                        grid: {
                            color: axisColor,
                            lineWidth: 1,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '{{ $type === "electricity" ? "Elektriciteit (kWh)" : "Gas (m³)" }}',
                            color: titleColor
                        },
                        ticks: {
                            color: titleColor
                        },
                        grid: {
                            color: axisColor,
                            lineWidth: 1
                        }
                    }
                }
            }
        });

        // Toggle comparison with last year
        const toggleButton = document.getElementById('toggle{{ ucfirst($type) }}Comparison{{ $loop->index ?? 0 }}');
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                const isVisible = chart.data.datasets.length > 1;
                if (isVisible) {
                    chart.data.datasets.pop();  // Remove the second dataset
                } else {
                    // Use the previous year data if available
                    const previousYearKey = `${dataKey}_previous_year`;
                    chart.data.datasets.push({
                        label: '{{ $type === "electricity" ? "kWh" : "m³" }} Verbruik Vorig Jaar',
                        data: chartData[previousYearKey] || [],
                        backgroundColor: '{{ $type === "electricity" ? "rgba(34, 197, 94, 0.6)" : "rgba(234, 88, 12, 0.6)" }}',
                        borderColor: '{{ $type === "electricity" ? "rgb(22, 163, 74)" : "rgb(234, 88, 12)" }}',
                        borderWidth: 1
                    });
                }
                chart.update();
            });
        }
    });
</script>
@endpush