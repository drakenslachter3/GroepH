@props(['title', 'type', 'unit', 'period', 'date' => null, 'buttonLabel', 'buttonColor', 'chartData', 'previousYearData'])

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

<section class="p-2" aria-labelledby="chart-widget-title">
    <div aria-label="{{ __('chart-widget.navigation_label') }}" class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
        <x-dashboard.widget-navigation :showPrevious="true" aria-label="{{ __('chart-widget.previous_widget') }}" />
        <x-dashboard.widget-heading :title="$title . ' (' . $unit . ')'" :type="$type" :date="$date" :period="$period" />
        <span tabindex="0" class="sr-only">{{ __('chart-widget.table_view_sr') }}</span>
        <x-dashboard.widget-navigation :showNext="true" aria-label="{{ __('chart-widget.next_widget') }}" />
        
        <div role="group" aria-label="{{ __('chart-widget.period_selection') }}" class="flex w-full sm:w-auto mt-2 sm:mt-0 overflow-hidden rounded-md">
            @foreach (['day' => __('chart-widget.day'), 'month' => __('chart-widget.month'), 'year' => __('chart-widget.year')] as $key => $label)
                <form method="GET" action="{{ route('dashboard') }}" class="m-0 p-0">
                    <input type="hidden" name="period" value="{{ $key }}">
                    <input type="hidden" name="date" value="{{ $date }}">
                    <input type="hidden" name="housing_type" value="{{ request('housing_type', 'tussenwoning') }}">
                    <button 
                        type="submit"
                        class="
                            px-3 py-1 text-sm transition-colors
                            {{ $loop->first ? 'rounded-l-md' : '' }}
                            {{ $loop->last ? 'rounded-r-md' : '' }}
                            {{ $period === $key 
                                ? 'bg-' . $buttonColor . '-500 text-white' 
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}"
                        aria-pressed="{{ $period === $key ? 'true' : 'false' }}"
                        aria-label="{{ __('chart-widget.show_data_per', ['period' => strtolower($label), 'current' => $period === $key ? __('chart-widget.current_setting') : '']) }}"
                        >
                        {{ $label }}
                    </button>
                </form>
            @endforeach
        </div>
    </div>
    
    <div role="region" aria-label="{{ __('chart-widget.date_navigation') }}" class="flex justify-between items-center mb-4">
        {{-- Previous Button --}}
        <form method="GET" action="{{ route('dashboard') }}" class="m-0 p-0" aria-label="{{ __('chart-widget.previous_period') }}">
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="date" value="{{ $previousDate->format('Y-m-d') }}">
            <input type="hidden" name="housing_type" value="{{ request('housing_type', 'tussenwoning') }}">
            <button type="submit" class="p-1 text-gray-500 hover:text-{{ $buttonColor }}-500 dark:text-gray-400 dark:hover:text-{{ $buttonColor }}-400" aria-label="{{ __('chart-widget.go_to_previous', ['period' => $period]) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
        </form>
    
        {{-- Label --}}
        <span class="px-3 py-1 text-sm bg-{{ $buttonColor }}-500 text-white dark:bg-{{ $buttonColor }}-600 dark:text-white rounded-md" aria-live="polite" aria-atomic="true">
            {{ $unitLabel }} {{ __('chart-widget.consumption') }}
        </span>
    
        {{-- Next Button --}}
        <form method="GET" action="{{ route('dashboard') }}" class="m-0 p-0" aria-label="{{ __('chart-widget.next_period') }}">
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="date" value="{{ $nextDate->format('Y-m-d') }}">
            <input type="hidden" name="housing_type" value="{{ request('housing_type', 'tussenwoning') }}">
            <button type="submit" class="p-1 text-gray-500 hover:text-{{ $buttonColor }}-500 dark:text-gray-400 dark:hover:text-{{ $buttonColor }}-400" aria-label="{{ __('chart-widget.go_to_next', ['period' => $period]) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
        </form>
    </div>
    
    
    <div class="relative" style="height: 300px;">
        <canvas id="{{ $type }}Chart"></canvas>
    </div>

    @php
        $currentData = $chartData[$dataKey] ?? [];
        $previousData = $previousYearData[$dataKey] ?? [];

        $currentTotal = array_sum($currentData);
        $previousTotal = array_sum($previousData);
    @endphp
    
    <div class="mt-4 flex flex-col gap-2 text-sm text-gray-800 dark:text-gray-100">
        <div class="flex items-center justify-between">
            <span class="font-medium">{{ __('chart-widget.current_year_total') }}:</span>
            <span>{{ number_format($currentTotal, 2, ',', '.') }} {{ $unit }}</span>
        </div>
        <div id="previous-year-total-{{$type}}" class="flex items-center justify-between transition-all duration-300 opacity-0 h-6 pointer-events-none">
            <span class="font-medium">{{ __('chart-widget.previous_year_total') }}:</span>
            <span>{{ number_format($previousTotal, 2, ',', '.') }} {{ $unit }}</span>
        </div>
    </div>

    
    <div class="mt-4 flex justify-end">
        <button id="toggle{{ ucfirst($type) }}Comparison" class="text-sm px-3 py-1 bg-{{ $buttonColor }}-100 text-{{ $buttonColor }}-700 rounded hover:bg-{{ $buttonColor }}-200 dark:bg-{{ $buttonColor }}-800 dark:text-{{ $buttonColor }}-100 dark:hover:bg-{{ $buttonColor }}-700">
            {{ $buttonLabel }}
        </button>
    </div>

    {{-- Accessible data table for the chart to support screen readers --}}
    <div class="focus:not-sr-only focus:absolute focus:z-10 focus:bg-white focus:dark:bg-gray-800 focus:p-4 focus:border focus:border-gray-300 focus:dark:border-gray-600 focus:shadow-lg focus:rounded-md focus:w-full focus:max-w-3xl">
        <div class="text-lg font-semibold mb-2 dark:text-white">
            @php
                $formattedPeriodDate = match($period) {
                    'day' => Carbon::parse($date)->translatedFormat('l j F Y'),
                    'month' => Carbon::parse($date)->translatedFormat('F Y'),
                    'year' => Carbon::parse($date)->translatedFormat('Y'),
                    default => ''
                };
            @endphp

            
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse table-auto">
                <thead>
                    <tr>
                        <th tabindex="0" id="{{ $type }}TableCaption" >
                            {{ $title }} - {{ __('chart-widget.consumption_overview', ['period' => $formattedPeriodDate]) }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalCurrent = 0;
                        $totalPrevious = 0;
                        $currentData = $chartData[$dataKey] ?? [];
                        $previousData = $previousYearData[$dataKey] ?? [];
                        $hasPreviousYearData = true;
                        $currentDate = Carbon::parse($date);
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
                                    $dateFormat = __('chart-widget.between_hours', ['start' => $hour, 'end' => $nextHour]);
                                    break;

                                case 'month':
                                    $dayDate = $currentDate->copy()->setDay($index + 1);
                                    $dayNumber = $index + 1;
                                    $dayFormat = "{$dayNumber}e";
                                    $dateFormat = __('chart-widget.on_day', ['day' => $dayFormat]);
                                    break;

                                case 'year':
                                    $months = [
                                        __('chart-widget.months.january'), __('chart-widget.months.february'), __('chart-widget.months.march'), 
                                        __('chart-widget.months.april'), __('chart-widget.months.may'), __('chart-widget.months.june'), 
                                        __('chart-widget.months.july'), __('chart-widget.months.august'), __('chart-widget.months.september'), 
                                        __('chart-widget.months.october'), __('chart-widget.months.november'), __('chart-widget.months.december')
                                    ];
                                    $monthName = $months[$index] ?? __('chart-widget.month_number', ['number' => $index + 1]);
                                    $dateFormat = __('chart-widget.in_month', ['month' => $monthName]);
                                    break;

                                default:
                                    $dateFormat = __('chart-widget.consumption_for_interval', ['index' => $index]);
                                    break;
                            }
                        @endphp
                        <tr>
                            <td scope="row" class="border dark:border-gray-700" tabindex="0">
                                {{ $dateFormat }} {{ number_format($value, 2, ',', '.') }} {{ $unit }}.
                                <span class="previous-year-comparison-{{$type}}" style="display: none;">
                                    @if($hasPreviousYearData && $prevValue !== null)
                                        {{ __('chart-widget.last_year_consumed', ['amount' => number_format($prevValue, 2, ',', '.'), 'unit' => $unit]) }}
                                        @if($diff !== null)
                                            {{ $diff < 0 ? __('chart-widget.you_saved') : __('chart-widget.you_consumed') }} {{ number_format(abs($diff), 2, ',', '.') }} {{ $unit }} {{ __('chart-widget.more_than_last_year') }}
                                        @endif
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td scope="row" class="border font-bold dark:border-gray-700" tabindex="0">
                            {{ __('chart-widget.total_consumed', ['amount' => number_format($totalCurrent, 2, ',', '.'), 'unit' => $unit]) }}
                            <span class="previous-year-comparison-{{$type}}" style="display: none;">
                                @if($hasPreviousYearData && $prevValue !== null)
                                    @php
                                        $totalDiff = $totalCurrent - $totalPrevious;
                                        $totalPercentChange = $totalPrevious != 0 ? (($totalCurrent - $totalPrevious) / $totalPrevious) * 100 : null;
                                    @endphp
                                    {{ __('chart-widget.last_year_total_consumed', ['amount' => number_format($totalPrevious, 2, ',', '.'), 'unit' => $unit]) }}
                                    {{ $totalDiff < 0 ? __('chart-widget.you_saved') : __('chart-widget.you_consumed') }} {{ number_format(abs($totalDiff), 2, ',', '.') }} {{ $unit }} {{ __('chart-widget.more_than_last_year') }}
                                @endif
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button onclick="document.getElementById('{{ $type }}TableCaption')?.focus();">
            {{ __('chart-widget.go_to_table_top', ['title' => $title]) }}
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
                    header.setAttribute('tabindex', '-1');
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
                periodTranslated = '{{ __("chart-widget.hours") }}';
                break;
            case 'month':
                const daysInMonth = {{ $daysInMonth }};
                for (let i = 1; i <= daysInMonth; i++) {
                    labels.push(i.toString());
                }
                periodTranslated = '{{ __("chart-widget.days") }}';
                break;
            case 'year':
                labels.push("{{ __('chart-widget.months.january') }}", "{{ __('chart-widget.months.february') }}", "{{ __('chart-widget.months.march') }}", 
                           "{{ __('chart-widget.months.april') }}", "{{ __('chart-widget.months.may') }}", "{{ __('chart-widget.months.june') }}", 
                           "{{ __('chart-widget.months.july') }}", "{{ __('chart-widget.months.august') }}", "{{ __('chart-widget.months.september') }}", 
                           "{{ __('chart-widget.months.october') }}", "{{ __('chart-widget.months.november') }}", "{{ __('chart-widget.months.december') }}");
                periodTranslated = '{{ __("chart-widget.months_label") }}';
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
                    label: '{{ $unitLabel }} {{ __("chart-widget.consumption") }}',
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
                            text: '{{ $type === "electricity" ? __("chart-widget.electricity_kwh") : __("chart-widget.gas_m3") }}',
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
        const toggleButton = document.getElementById('toggle{{ ucfirst($type) }}Comparison');
        if (toggleButton) {
            toggleButton.addEventListener('click', function () {
                const isVisible = chart.data.datasets.length > 1;
                const previousTotalEl = document.getElementById('previous-year-total-{{$type}}');
                const previousYearComparisons = document.querySelectorAll('.previous-year-comparison-{{$type}}');

                if (isVisible) {
                    // Hide comparison
                    chart.data.datasets.pop();
                    if (previousTotalEl) {
                        previousTotalEl.classList.remove('opacity-100');
                        previousTotalEl.classList.add('opacity-0', 'pointer-events-none');
                    }
                    // Hide previous year comparison text in table
                    previousYearComparisons.forEach(element => {
                        element.style.display = 'none';
                    });
                } else {
                    // Show comparison
                    chart.data.datasets.push({
                        label: '{{$unit}} {{ __("chart-widget.consumption_last_year") }}',
                        data: @json($previousData),
                        backgroundColor: '{{ $type === "electricity" ? "rgba(139, 92, 246, 0.6)" : "rgba(251, 191, 36, 0.6)" }}',
                        borderColor: '{{ $type === "electricity" ? "rgb(124, 58, 237)" : "rgb(202, 138, 4)" }}',
                        borderWidth: 1
                    });
                    if (previousTotalEl) {
                        previousTotalEl.classList.remove('opacity-0', 'pointer-events-none');
                        previousTotalEl.classList.add('opacity-100');
                    }
                    // Show previous year comparison text in table
                    previousYearComparisons.forEach(element => {
                        element.style.display = 'inline';
                    });
                }

                chart.update();
            });
        }
    });
</script>
@endpush