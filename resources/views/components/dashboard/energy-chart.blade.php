{{-- resources/views/components/dashboard/energy-chart-widget.blade.php --}}

@props(['title', 'type', 'unit', 'period', 'date' => null, 'buttonColor', 'chartData', 'previousYearData', 'outages' => []])

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

    // Dynamic labels based on period
    $currentPeriodLabel = match($period) {
        'day' => $currentDate->isToday() ? 'Totaal vandaag' : 'Totaal op ' . $currentDate->translatedFormat('j F'),
        'month' => $currentDate->isSameMonth(Carbon::now()) ? 'Totaal deze maand' : 'Totaal in ' . $currentDate->translatedFormat('F Y'),
        'year' => $currentDate->isSameYear(Carbon::now()) ? 'Totaal dit jaar' : 'Totaal in ' . $currentDate->format('Y'),
        default => 'Totaal'
    };

    $previousPeriodLabel = match($period) {
        'day' => 'Vorige dag',
        'month' => 'Vorige maand',
        'year' => 'Vorig jaar',
        default => 'Vorige periode'
    };
@endphp

<section class="p-2" aria-labelledby="chart-widget-title">
    <div aria-label="{{ __('energy-chart-widget.navigation_label') }}" class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
        <x-dashboard.widget-navigation :showPrevious="true" aria-label="{{ __('energy-chart-widget.previous_widget') }}" />
        <x-dashboard.widget-heading :title="$title . ' (' . $unit . ')'" :type="$type" :date="$date" :period="$period" />
        <span tabindex="0" class="sr-only">{{ __('energy-chart-widget.table_view_sr') }}</span>
        <x-dashboard.widget-navigation :showNext="true" aria-label="{{ __('energy-chart-widget.next_widget') }}" />

        <div role="group" aria-label="{{ __('energy-chart-widget.period_selection') }}" class="flex w-full sm:w-auto mt-2 sm:mt-0 overflow-hidden rounded-md">
            @foreach (['day' => __('general.day'), 'month' => __('general.month'), 'year' => __('general.year')] as $key => $label)
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
                        aria-label="{{ __('general.show_data_per', ['period' => $label, 'current' => $period === $key ? __('general.current_setting') : '']) }}"
                        >
                        {{ $label }}
                    </button>
                </form>
            @endforeach
        </div>
    </div>

    <div class="flex justify-between items-center mb-4">
        {{-- Previous Button --}}
        <form method="GET" action="{{ route('dashboard') }}" class="m-0 p-0">
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="date" value="{{ $previousDate->format('Y-m-d') }}">
            <input type="hidden" name="housing_type" value="{{ request('housing_type', 'tussenwoning') }}">
            <button type="submit" class="p-1 text-gray-500 hover:text-{{ $buttonColor }}-500 dark:text-gray-400 dark:hover:text-{{ $buttonColor }}-400" aria-label="{{ __('general.go_to_previous', ['period' => __('general.' . $period)]) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
        </form>

        {{-- Label --}}
        <span class="px-3 py-1 text-sm bg-{{ $buttonColor }}-500 text-white dark:bg-{{ $buttonColor }}-600 dark:text-white rounded-md" aria-live="polite" aria-atomic="true">
            {{ $unitLabel }} {{ __('energy-chart-widget.consumption') }}
        </span>

        {{-- Next Button --}}
        <form method="GET" action="{{ route('dashboard') }}" class="m-0 p-0">
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="date" value="{{ $nextDate->format('Y-m-d') }}">
            <input type="hidden" name="housing_type" value="{{ request('housing_type', 'tussenwoning') }}">
            <button type="submit" class="p-1 text-gray-500 hover:text-{{ $buttonColor }}-500 dark:text-gray-400 dark:hover:text-{{ $buttonColor }}-400" aria-label="{{ __('general.go_to_next', ['period' => __('general.' . $period)]) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
        </form>
    </div>


    <div class="relative" style="height: 300px;">
        <canvas id="{{ $type }}Chart"></canvas>
    </div>

    {{-- InfluxDB Outage Legend --}}
    @if($outages && count($outages) > 0)
        <div class="mt-2 flex items-center justify-center">
            <div class="flex items-center space-x-4 text-sm">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-{{ $buttonColor }}-500 opacity-60 rounded mr-2"></div>
                    <span>Normaal Verbruik</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-orange-500 opacity-80 rounded mr-2"></div>
                    <span>Moment van storing</span>
                </div>
            </div>
        </div>
    @endif

    @php
        $currentData = $chartData[$dataKey] ?? [];
        $previousData = $previousYearData[$dataKey] ?? [];

        // Use the same calculation method as the dashboard controller for consistency
        $currentTotal = 0;
        $previousTotal = 0;

        // Calculate totals by filtering out null values and only including actual data
        foreach ($currentData as $index => $value) {
            if ($value !== null && is_numeric($value)) {
                $currentTotal += $value;
            }
        }

        foreach ($previousData as $index => $value) {
            if ($value !== null && is_numeric($value)) {
                $previousTotal += $value;
            }
        }
        $colors = [
            'yellow' => [
                'bg' => 'bg-yellow-100',
                'text' => 'text-yellow-700',
                'hover' => 'hover:bg-yellow-200',
                'darkBg' => 'dark:bg-yellow-800',
                'darkText' => 'dark:text-yellow-100',
                'darkHover' => 'dark:hover:bg-yellow-700',
            ],
            'blue' => [
                'bg' => 'bg-blue-100',
                'text' => 'text-blue-700',
                'hover' => 'hover:bg-blue-200',
                'darkBg' => 'dark:bg-blue-800',
                'darkText' => 'dark:text-blue-100',
                'darkHover' => 'dark:hover:bg-blue-700',
            ],
        ];
        $c = $colors[$buttonColor] ?? $colors['blue'];
    @endphp

    <div class="mt-4 flex flex-col gap-2 text-sm text-gray-800 dark:text-gray-100">
        <div class="flex items-center justify-between">
            <span class="font-medium">{{ $currentPeriodLabel }}:</span>
            <span>{{ number_format($currentTotal, 2, ',', '.') }} {{ $unit }}</span>
        </div>
        <div id="previous-year-total-{{$type}}" class="flex items-center justify-between transition-all duration-300 opacity-0 h-6 pointer-events-none">
            <span class="font-medium">{{ $previousPeriodLabel }}:</span>
            <span>{{ number_format($previousTotal, 2, ',', '.') }} {{ $unit }}</span>
        </div>
    </div>


    <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-2">
        <form id="comparison-date-form-{{ $type }}" method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 m-0 p-0" style="display: block;">
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="date" value="{{ $date }}">
            <input type="hidden" name="housing_type" value="{{ request('housing_type', 'tussenwoning') }}">
            <label for="comparison-date-{{ $type }}" class="text-sm font-medium text-gray-700 dark:text-gray-200 mr-2">
                {{ __('energy-chart-widget.select_comparison_date') }}
            </label>
            @php
                $maxDate = match($period) {
                    'day' => Carbon::parse($date)->subDay()->format('Y-m-d'),
                    'month' => Carbon::parse($date)->subMonthNoOverflow()->format('Y-m'),
                    'year' => Carbon::parse($date)->subYear()->format('Y'),
                    default => Carbon::now()->subDay()->format('Y-m-d')
                };
                $minDate = '2020-01-01';
                $inputType = match($period) {
                    'day' => 'date',
                    'month' => 'month',
                    'year' => 'number',
                    default => 'date'
                };
                $defaultComparison = request('comparison_date') ?: match($period) {
                    'day' => Carbon::parse($date)->subYear()->format('Y-m-d'),
                    'month' => Carbon::parse($date)->subYear()->format('Y-m'),
                    'year' => Carbon::parse($date)->subYear()->format('Y'),
                    default => Carbon::parse($date)->subYear()->format('Y-m-d')
                };
            @endphp
                <input
                id="comparison-date-{{ $type }}"
                name="comparison_date"
                type="{{ $inputType }}"
                min="{{ $period === 'year' ? '2020' : $minDate }}"
                max="{{ $period === 'year' ? now()->year - 1 : $maxDate }}"
                step="1"
                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2 py-1 text-sm focus:ring-2 focus:ring-{{ $buttonColor }}-500 focus:border-{{ $buttonColor }}-500 transition-colors"
                style="min-width: 110px;"
                value="{{ $defaultComparison }}"
                aria-label="{{ __('energy-chart-widget.select_comparison_date_label') }}"
                onchange="this.form.submit()"
            >
        </form>
        <button id="toggle{{ ucfirst($type) }}Comparison"
            class="text-sm px-3 py-1 rounded {{ $c['bg'] }} {{ $c['text'] }} {{ $c['hover'] }} {{ $c['darkBg'] }} {{ $c['darkText'] }} {{ $c['darkHover'] }}">
            {{ __('energy-chart-widget.show_comparison') }}
        </button>
    </div>

    {{-- Accessible table for screen reader --}}
    <div class="sr-only focus-within:not-sr-only focus:not-sr-only">
        <h2 tabindex="0" class="text-lg font-semibold mb-2 dark:text-white" id="{{ $type }}TableCaption">
            @php
                $formattedPeriodDate = match($period) {
                    'day' => Carbon::parse($date)->translatedFormat('l j F Y'),
                    'month' => Carbon::parse($date)->translatedFormat('F Y'),
                    'year' => Carbon::parse($date)->translatedFormat('Y'),
                    default => ''
                };
            @endphp
            {{ $title }} - {{ __('energy-chart-widget.consumption_overview', ['period' => $formattedPeriodDate]) }}
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse table-auto"
                role="table"
                aria-labelledby="{{ $type }}TableCaption"
                aria-describedby="{{ $type }}TableDescription">

                <!-- Table description for context -->
                <caption class="sr-only" id="{{ $type }}TableDescription">
                    {{ __('energy-chart-widget.table_description', [
                        'type' => $type,
                        'period' => $formattedPeriodDate,
                        'unit' => $unit
                    ]) }}
                </caption>

                <thead>
                    <tr role="row">
                        <th scope="col"
                            role="columnheader"
                            tabindex="0"
                            class="border p-2 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 text-left font-semibold">
                            {{ __('energy-chart-widget.period_column') }}
                        </th>
                        <th scope="col"
                            role="columnheader"
                            tabindex="0"
                            class="border p-2 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 text-left font-semibold">
                            {{ __('energy-chart-widget.consumption_column') }} ({{ $unit }})
                        </th>
                        <th scope="col"
                            role="columnheader"
                            tabindex="0"
                            class="border p-2 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 text-left font-semibold previous-year-comparison-{{$type}}"
                            style="display: none;">
                            {{ $previousPeriodLabel }} ({{ $unit }})
                        </th>
                        <th scope="col"
                            role="columnheader"
                            tabindex="0"
                            class="border p-2 bg-gray-50 dark:bg-gray-800 dark:border-gray-700 text-left font-semibold previous-year-comparison-{{$type}}"
                            style="display: none;">
                            {{ __('energy-chart-widget.difference_column') }}
                        </th>
                    </tr>
                </thead>

                <tbody role="rowgroup">
                    @php
                        $totalCurrent = 0;
                        $totalPrevious = 0;
                        $currentData = $chartData[$dataKey] ?? [];
                        $previousData = $previousYearData[$dataKey] ?? [];
                        $hasPreviousYearData = !empty($previousData);
                        $currentDate = Carbon::parse($date);
                    @endphp

                    @foreach($currentData as $index => $value)
                        @php
                            // Only add to total if value is not null and numeric
                            if ($value !== null && is_numeric($value)) {
                                $totalCurrent += $value;
                            }

                            $prevValue = $previousData[$index] ?? null;
                            if ($prevValue !== null && is_numeric($prevValue)) {
                                $totalPrevious += $prevValue;
                            }

                            $diff = ($prevValue !== null && $value !== null) ? $value - $prevValue : null;
                            $percentChange = ($prevValue && $prevValue != 0) ? (($value - $prevValue) / $prevValue) * 100 : null;

                            switch($period) {
                                case 'day':
                                    $hour = str_pad($index, 2, '0', STR_PAD_LEFT);
                                    $nextHour = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                                    $dateFormat = __('energy-chart-widget.between_hours', ['start' => $hour, 'end' => $nextHour]);
                                    break;

                                case 'month':
                                    $dayDate = $currentDate->copy()->setDay($index + 1);
                                    $dayNumber = $index + 1;
                                    $dayFormat = "{$dayNumber}e";
                                    $dateFormat = __('energy-chart-widget.on_day', ['day' => $dayFormat]);
                                    break;

                                case 'year':
                                    $months = [
                                        __('energy-chart-widget.months.january'), __('energy-chart-widget.months.february'), __('energy-chart-widget.months.march'),
                                        __('energy-chart-widget.months.april'), __('energy-chart-widget.months.may'), __('energy-chart-widget.months.june'),
                                        __('energy-chart-widget.months.july'), __('energy-chart-widget.months.august'), __('energy-chart-widget.months.september'),
                                        __('energy-chart-widget.months.october'), __('energy-chart-widget.months.november'), __('energy-chart-widget.months.december')
                                    ];
                                    $monthName = $months[$index] ?? __('energy-chart-widget.month_number', ['number' => $index + 1]);
                                    $dateFormat = __('energy-chart-widget.in_month', ['month' => $monthName]);
                                    break;

                                default:
                                    $dateFormat = __('energy-chart-widget.consumption_for_interval', ['index' => $index]);
                                    break;
                            }
                        @endphp
                        <tr role="row">
                            <td role="gridcell"
                                tabindex="0"
                                class="border p-2 dark:border-gray-700"
                                aria-describedby="{{ $type }}TableDescription">
                                {{ $dateFormat }}
                            </td>
                            <td role="gridcell"
                                tabindex="0"
                                class="border p-2 dark:border-gray-700 font-mono"
                                aria-label="{{ __('energy-chart-widget.consumption_amount', ['amount' => number_format($value ?? 0, 2, ',', '.'), 'unit' => $unit]) }}">
                                {{ $value !== null ? number_format($value, 2, ',', '.') : '-' }}
                            </td>
                            <td role="gridcell"
                                tabindex="0"
                                class="border p-2 dark:border-gray-700 font-mono previous-year-comparison-{{$type}}"
                                style="display: none;"
                                aria-label="{{ $prevValue !== null ? __('energy-chart-widget.previous_year_amount', ['amount' => number_format($prevValue, 2, ',', '.'), 'unit' => $unit]) : __('energy-chart-widget.no_data') }}">
                                @if($prevValue !== null)
                                    {{ number_format($prevValue, 2, ',', '.') }}
                                @else
                                    {{ __('energy-chart-widget.no_data') }}
                                @endif
                            </td>
                            <td role="gridcell"
                                tabindex="0"
                                class="border p-2 dark:border-gray-700 previous-year-comparison-{{$type}}"
                                style="display: none;"
                                aria-label="{{ $diff !== null ? ($diff < 0 ? __('energy-chart-widget.saved') : __('energy-chart-widget.used_more')) . ' ' . number_format(abs($diff), 2, ',', '.') . ' ' . $unit : __('energy-chart-widget.no_comparison') }}">
                                @if($diff !== null)
                                    <span class="{{ $diff < 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $diff < 0 ? '-' : '+' }}{{ number_format(abs($diff), 2, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-gray-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot role="rowgroup">
                    <tr role="row" class="bg-gray-50 dark:bg-gray-800">
                        <th scope="row"
                            tabindex="0"
                            class="border p-2 font-bold dark:border-gray-700 text-left">
                            {{ __('energy-chart-widget.total') }}
                        </th>
                        <td role="gridcell"
                            tabindex="0"
                            class="border p-2 font-bold dark:border-gray-700 font-mono"
                            aria-label="{{ __('energy-chart-widget.total_consumption_amount', ['amount' => number_format($totalCurrent, 2, ',', '.'), 'unit' => $unit]) }}">
                            {{ number_format($totalCurrent, 2, ',', '.') }}
                        </td>
                        <td role="gridcell"
                            tabindex="0"
                            class="border p-2 font-bold dark:border-gray-700 font-mono previous-year-comparison-{{$type}}"
                            style="display: none;"
                            aria-label="{{ $hasPreviousYearData ? __('energy-chart-widget.total_previous_year_amount', ['amount' => number_format($totalPrevious, 2, ',', '.'), 'unit' => $unit]) : __('energy-chart-widget.no_data') }}">
                            @if($hasPreviousYearData)
                                {{ number_format($totalPrevious, 2, ',', '.') }}
                            @else
                                {{ __('energy-chart-widget.no_data') }}
                            @endif
                        </td>
                        <td role="gridcell"
                            tabindex="0"
                            class="border p-2 font-bold dark:border-gray-700 previous-year-comparison-{{$type}}"
                            style="display: none;"
                            aria-label="{{ $hasPreviousYearData ? (($totalCurrent - $totalPrevious) < 0 ? __('energy-chart-widget.total_saved') : __('energy-chart-widget.total_used_more')) . ' ' . number_format(abs($totalCurrent - $totalPrevious), 2, ',', '.') . ' ' . $unit : __('energy-chart-widget.no_comparison') }}">
                            @if($hasPreviousYearData)
                                @php $totalDiff = $totalCurrent - $totalPrevious; @endphp
                                <span class="{{ $totalDiff < 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $totalDiff < 0 ? '-' : '+' }}{{ number_format(abs($totalDiff), 2, ',', '.') }}
                                </span>
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-4 not-sr-only">
            <button type="button"
                    onclick="scrollAndFocus('{{ $type }}TableCaption')"
                    tabindex="0"
                    class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-600 dark:hover:bg-blue-700">
                {{ __('energy-chart-widget.go_to_table_top', ['title' => $title]) }}
            </button>
        </div>
    </div>
</section>

@push('chart-scripts')
<script>
    function scrollAndFocus(id) {
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            el.setAttribute('tabindex', '0');
            el.focus();
        }
    }
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
        const previousData = @json($previousYearData[$dataKey] ?? []);
        const comparisonDate = '{{ request('comparison_date', '') }}';
        const outagesData = @json($outages);
        const period = "{{ $period }}";
        const chartDate = "{{ $date }}";

        // Function to check if a time period has an outage
        function hasOutage(index, period, date) {
            if (!outagesData || outagesData.length === 0) return false;

            const currentDate = new Date(date);
            let periodStart, periodEnd;

            switch(period) {
                case 'day':
                    // For hourly data (index 0-23)
                    periodStart = new Date(currentDate);
                    periodStart.setHours(index, 0, 0, 0);
                    periodEnd = new Date(currentDate);
                    periodEnd.setHours(index, 59, 59, 999);
                    break;

                case 'month':
                    // For daily data (index 0-30)
                    periodStart = new Date(currentDate.getFullYear(), currentDate.getMonth(), index + 1, 0, 0, 0);
                    periodEnd = new Date(currentDate.getFullYear(), currentDate.getMonth(), index + 1, 23, 59, 59);
                    break;

                case 'year':
                    // For monthly data (index 0-11)
                    periodStart = new Date(currentDate.getFullYear(), index, 1, 0, 0, 0);
                    periodEnd = new Date(currentDate.getFullYear(), index + 1, 0, 23, 59, 59);
                    break;

                default:
                    return false;
            }

            // Check if any outage overlaps with this period
            return outagesData.some(outage => {
                const outageStart = new Date(outage.start_time);
                const outageEnd = outage.end_time ? new Date(outage.end_time) : new Date(); // If no end time, use current time

                // Check for overlap: outage starts before period ends AND outage ends after period starts
                return outageStart <= periodEnd && outageEnd >= periodStart;
            });
        }

        let periodTranslated;
        const labels = [];

        // Generate labels based on period
        switch(period) {
            case 'day':
                // For day view - 24 hours (0-23)
                for (let i = 0; i < 24; i++) {
                    const hour = i.toString().padStart(2, '0');
                    labels.push(`${hour}:00`);
                }
                periodTranslated = '{{ __("energy-chart-widget.hours") }}';
                break;
            case 'month':
                const daysInMonth = {{ $daysInMonth }};
                for (let i = 1; i <= daysInMonth; i++) {
                    labels.push(i.toString());
                }
                periodTranslated = '{{ __("energy-chart-widget.days") }}';
                break;
            case 'year':
                labels.push("{{ __('energy-chart-widget.months.january') }}", "{{ __('energy-chart-widget.months.february') }}", "{{ __('energy-chart-widget.months.march') }}",
                           "{{ __('energy-chart-widget.months.april') }}", "{{ __('energy-chart-widget.months.may') }}", "{{ __('energy-chart-widget.months.june') }}",
                           "{{ __('energy-chart-widget.months.july') }}", "{{ __('energy-chart-widget.months.august') }}", "{{ __('energy-chart-widget.months.september') }}",
                           "{{ __('energy-chart-widget.months.october') }}", "{{ __('energy-chart-widget.months.november') }}", "{{ __('energy-chart-widget.months.december') }}");
                periodTranslated = '{{ __("energy-chart-widget.months_label") }}';
                break;
            default:
                console.error("Unknown period:", period);
        }

        const chartCanvas = document.getElementById('{{ $type }}Chart');

        if (!chartCanvas) {
            console.error('Canvas element not found: {{ $type }}Chart');
            return;
        }

        // Get the correct data key based on the type
        const dataKey = "{{ $dataKey }}";
        const usageData = chartData[dataKey] || [];

        // Keep normal bar colors
        const normalBgColor = '{{ $backgroundColor }}';
        const normalBorderColor = '{{ $borderColor }}';

        // Create outage zones for background
        const outageZones = [];
        for (let i = 0; i < usageData.length; i++) {
            if (hasOutage(i, period, chartDate)) {
                outageZones.push({
                    index: i,
                    hasOutage: true
                });
            }
        }

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
                    label: '{{ $unitLabel }} {{ __("energy-chart-widget.consumption") }}',
                    data: usageData,
                    backgroundColor: normalBgColor, // Keep normal colors
                    borderColor: normalBorderColor, // Keep normal colors
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
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                // Add outage indicator to tooltip
                                if (hasOutage(context.dataIndex, period, chartDate)) {
                                    return '⚠️ InfluxDB Uitval Gedetecteerd';
                                }
                                return '';
                            }
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
                            text: '{{ $type === "electricity" ? __("energy-chart-widget.electricity_kwh") : __("energy-chart-widget.gas_m3") }}',
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
                },
                onHover: function(event, activeElements) {
                    // Optional: change cursor on hover
                },
                animation: {
                    onComplete: function() {
                        // Draw outage backgrounds after chart is rendered
                        drawOutageBackgrounds();
                    }
                }
            },
            plugins: [{
                id: 'outageBackground',
                beforeDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    const chartArea = chart.chartArea;

                    // Draw orange background for outage periods
                    outageZones.forEach(zone => {
                        if (zone.hasOutage) {
                            const meta = chart.getDatasetMeta(0);
                            const bar = meta.data[zone.index];

                            if (bar) {
                                const barWidth = bar.width;
                                const x = bar.x - barWidth / 2;

                                ctx.save();
                                ctx.fillStyle = 'rgba(249, 115, 22, 0.2)'; // Light orange background
                                ctx.fillRect(x, chartArea.top, barWidth, chartArea.bottom - chartArea.top);
                                ctx.restore();
                            }
                        }
                    });
                }
            }]
        });

        // Function to draw outage backgrounds (alternative method)
        function drawOutageBackgrounds() {
            const ctx = chart.ctx;
            const chartArea = chart.chartArea;

            outageZones.forEach(zone => {
                if (zone.hasOutage) {
                    const meta = chart.getDatasetMeta(0);
                    const bar = meta.data[zone.index];

                    if (bar) {
                        const barWidth = bar.width;
                        const x = bar.x - barWidth / 2;

                        ctx.save();
                        ctx.globalCompositeOperation = 'destination-over';
                        ctx.fillStyle = 'rgba(249, 115, 22, 0.2)'; // Light orange background
                        ctx.fillRect(x, chartArea.top, barWidth, chartArea.bottom - chartArea.top);
                        ctx.restore();
                    }
                }
            });
        }

        // Toggle comparison with last year - Updated version
        const toggleButton = document.getElementById('toggle{{ ucfirst($type) }}Comparison');
        const previousTotalEl = document.getElementById('previous-year-total-{{$type}}');
        const previousYearComparisons = document.querySelectorAll('.previous-year-comparison-{{$type}}');

        function showComparison() {
            if (chart.data.datasets.length < 2) {
                chart.data.datasets.push({
                    label: '{{$unit}} {{ __("energy-chart-widget.consumption_last_year") }}',
                    data: previousData,
                    backgroundColor: '{{ $type === "electricity" ? "rgba(139, 92, 246, 0.6)" : "rgba(251, 191, 36, 0.6)" }}',
                    borderColor: '{{ $type === "electricity" ? "rgb(124, 58, 237)" : "rgb(202, 138, 4)" }}',
                    borderWidth: 1
                });
                if (previousTotalEl) {
                    previousTotalEl.classList.remove('opacity-0', 'pointer-events-none');
                    previousTotalEl.classList.add('opacity-100');
                }
                previousYearComparisons.forEach(element => {
                    element.style.display = '';
                    element.setAttribute('tabindex', '0');
                });
                toggleButton.textContent = '{{ __("energy-chart-widget.hide_comparison") }}';
                chart.update();
            }
        }

        function hideComparison() {
            if (chart.data.datasets.length > 1) {
                chart.data.datasets.pop();
                if (previousTotalEl) {
                    previousTotalEl.classList.remove('opacity-100');
                    previousTotalEl.classList.add('opacity-0', 'pointer-events-none');
                }
                previousYearComparisons.forEach(element => {
                    element.style.display = 'none';
                    element.setAttribute('tabindex', '-1');
                });
                toggleButton.textContent = '{{ __("energy-chart-widget.show_comparison") }}';
                chart.update();
            }
        }

        if (toggleButton) {
            toggleButton.addEventListener('click', function () {
                const isVisible = chart.data.datasets.length > 1;
                const previousTotalEl = document.getElementById('previous-year-total-{{$type}}');
                const previousYearComparisons = document.querySelectorAll('.previous-year-comparison-{{$type}}');

                if (isVisible) {
                    hideComparison();
                } else {
                    showComparison();
                }

                // Announce the change to screen readers
                const announcement = isVisible ?
                    '{{ __("energy-chart-widget.comparison_hidden") }}' :
                    '{{ __("energy-chart-widget.comparison_shown") }}';

                // Create temporary announcement for screen readers
                const announcer = document.createElement('div');
                announcer.setAttribute('aria-live', 'polite');
                announcer.setAttribute('aria-atomic', 'true');
                announcer.className = 'sr-only';
                announcer.textContent = announcement;
                document.body.appendChild(announcer);
                setTimeout(() => {
                    document.body.removeChild(announcer);
                }, 1000);
            });
        }

        // Auto-show comparison if comparison_date is set
        if (comparisonDate) {
            showComparison();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('toggle{{ ucfirst($type) }}Comparison');
        const comparisonForm = document.getElementById('comparison-date-form-{{ $type }}');
        const comparisonDate = '{{ request('comparison_date', '') }}';
        function showComparisonForm() {
            if (comparisonForm) comparisonForm.style.display = '';
        }
        function hideComparisonForm() {
            if (comparisonForm) comparisonForm.style.display = 'none';
        }
        if (toggleButton) {
            toggleButton.addEventListener('click', function () {
                const isVisible = chart.data.datasets.length > 1;
                if (!isVisible) {
                    showComparisonForm();
                } else {
                    hideComparisonForm();
                }
            });
        }
        // Show form if comparison is active or comparison_date is set
        if (comparisonDate) {
            showComparisonForm();
        }
    });
</script>
@endpush