@props(['type', 'title', 'buttonLabel', 'buttonColor', 'chartData', 'period', 'date' => null])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
    <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
            <div class="flex flex-col mb-2 sm:mb-0">
                <h3 class="text-lg font-semibold dark:text-white">{{ $title }}</h3>
                
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
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            return;
        }

        const chartData = @json($chartData);

        const labels = [];
        switch("{{ $period }}") {
            case 'day':
                labels.push("00:00", "01:00", "02:00", "03:00", "04:00", "05:00", "06:00", "07:00",
                            "08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00",
                            "16:00", "17:00", "18:00", "19:00", "20:00", "21:00", "22:00", "23:00");
                break;
            case 'month':
                for (let i = 1; i <= 31; i++) {
                    labels.push(i.toString());
                }
                break;
            case 'year':
                labels.push("January", "February", "March", "April", "May", "June", "July", "August", 
                            "September", "October", "November", "December");
                break;
            default:
                console.error("Unknown period:", "{{ $period }}");
        }
        
        const chartCanvas = document.getElementById('{{ $type }}Chart{{ $loop->index ?? 0 }}');
        
        if (!chartCanvas) {
            console.error('Canvas element not found: {{ $type }}Chart{{ $loop->index ?? 0 }}');
            return;
        }
        
        const ctx = chartCanvas.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '{{ $type === "electricity" ? "kWh" : "m³" }} Verbruik',
                    data: chartData['{{ $type }}_usage'] || [],
                    backgroundColor: '{{ $type === "electricity" ? "rgba(59, 130, 246, 0.6)" : "rgba(245, 158, 11, 0.6)" }}',
                    borderColor: '{{ $type === "electricity" ? "rgb(37, 99, 235)" : "rgb(217, 119, 6)" }}',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: '{{ ucfirst($period) }}',
                            color: '#000'
                        },
                        ticks: {
                            color: '#4B5563'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '{{ $type === "electricity" ? "Elektriciteit (kWh)" : "Gas (m³)" }}',
                            color: '#000'
                        },
                        ticks: {
                            color: '#4B5563'
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
                    chart.data.datasets.push({
                        label: 'Verbruik Vorig Jaar',
                        data: chartData['{{ $type }}_usage_previous_year'] || [],
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