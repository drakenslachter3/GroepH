@props(['title', 'type' => null, 'date' => null, 'period' => null])

<div>
    @php
        if ($date && $period) {
            $capitalizeMonthInDayFormat = function($date) {
                $parts = explode(' ', $date);
                if (isset($parts[1])) {
                    $parts[1] = ucfirst($parts[1]);
                }
                return implode(' ', $parts);
            };

            $capitalizeMonthInMonthFormat = function($date) {
                $parts = explode(' ', $date);
                if (isset($parts[0])) {
                    $parts[0] = ucfirst($parts[0]);
                }
                return implode(' ', $parts);
            };

            $carbonDate = \Carbon\Carbon::parse($date);
            $dayFormat = $carbonDate->translatedFormat('d F Y');
            $monthFormat = $carbonDate->translatedFormat('F Y');
            $yearFormat = $carbonDate->translatedFormat('Y');

            $formattedDate = match($period) {
                'day' => $capitalizeMonthInDayFormat($dayFormat),
                'month' => $capitalizeMonthInMonthFormat($monthFormat),
                'year' => $yearFormat,
                default => $capitalizeMonthInDayFormat($dayFormat),
            };
        } else {
            $formattedDate = null;
        }

        $ariaLabel = $title;
        if ($formattedDate) {
            $ariaLabel .= ' ' . $formattedDate;
        }
    @endphp

    <h3 id="{{$title}}-widget-title-{{ $type }}" class="text-lg font-semibold dark:text-white" tabindex="0" aria-label="{{ $ariaLabel }}">
        {{ $title }}
        @if($formattedDate)
            <span class="sr-only text-sm font-normal text-gray-500 dark:text-gray-300 ml-2">
                {{ $formattedDate }}
            </span>
        @endif
    </h3>

    @if($formattedDate)
        <div class="mt-1 inline-block bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-md text-xs font-medium text-gray-700 dark:text-gray-300">
            <span id="widget-date">{{ $formattedDate }}</span>
        </div>
    @endif
</div>
