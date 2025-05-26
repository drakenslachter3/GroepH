@props(['type', 'title', 'buttonLabel', 'buttonColor', 'chartData', 'period', 'date' => null])

@php
    use Carbon\Carbon;
    
    // Handle date formatting properly based on period
    $formattedDate = $date;
    if ($period == 'month' && $date) {
        // For month view, ensure we have the correct format for calculating days in month
        $dateObj = Carbon::parse($date);
        $formattedDate = $dateObj->format('Y-m');
        $daysInMonth = Carbon::createFromFormat('Y-m', $formattedDate)->daysInMonth;
    } else {
        $daysInMonth = 30; // Default fallback
    }
    
    // Define the data key mapping based on the type
    $dataKey = $type === 'electricity' ? 'energy_consumed' : 'gas_delivered';
    $unitLabel = $type === 'electricity' ? 'kWh' : 'm³';
    $backgroundColor = $type === 'electricity' ? 'rgba(59, 130, 246, 0.6)' : 'rgba(245, 158, 11, 0.6)';
    $borderColor = $type === 'electricity' ? 'rgb(37, 99, 235)' : 'rgb(217, 119, 6)';
    
    // Parse the current date for navigation
    $currentDate = Carbon::parse($date);
    
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
@endphp

<div class="p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
        <div class="flex flex-col mb-2 sm:mb-0">
            <h3 class="text-lg font-semibold dark:text-white">{{ $title }}</h3>
            
            <div class="mt-1 text-sm text-sky-600 dark:text-sky-300 font-medium">
                @switch($period)
                    @case('day')
                        {{ Carbon::parse($date)->format('d F Y') }}
                        @break
                    @case('month')
                        {{ Carbon::parse($date)->format('F Y') }}
                        @break
                    @case('year')
                        {{ Carbon::parse($date)->format('Y') }}
                        @break
                @endswitch
            </div>
        </div>
        
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
</div>

@push('chart-scripts')
<script>
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
                labels.push("00:00", "01:00", "02:00", "03:00", "04:00", "05:00", "06:00", "07:00",
                            "08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00",
                            "16:00", "17:00", "18:00", "19:00", "20:00", "21:00", "22:00", "23:00");
                periodTranslated = 'Uren';
                break;
                
            case 'month':
                // For month view - days in selected month
                const daysInMonth = {{ $daysInMonth }};
                for (let i = 1; i <= daysInMonth; i++) {
                    labels.push(i.toString());
                }
                periodTranslated = 'Dagen';
                break;
                
            case 'year':
                // For year view - 12 months
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
        
        const axisColor = isDarkMode ? '#D1D5DB' : '#4B5563';
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
                            color: axisColor
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
                            color: axisColor
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
                            color: axisColor
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