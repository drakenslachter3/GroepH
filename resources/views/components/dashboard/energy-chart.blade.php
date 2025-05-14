@props([
    'type' => 'electricity',
    'title' => 'Energy Chart',
    'buttonLabel' => 'Show Last Year',
    'buttonColor' => 'blue',
    'chartData' => [],
    'period' => 'day',
    'date' => null
])

<div class="bg-white rounded-lg shadow-md p-6 dark:bg-gray-800">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
        <button 
            id="toggleChart{{ ucfirst($type) }}" 
            class="px-4 py-2 rounded-md text-white transition-colors duration-200
                   {{ $buttonColor === 'blue' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-yellow-600 hover:bg-yellow-700' }}"
            onclick="toggleChart{{ ucfirst($type) }}()">
            {{ $buttonLabel }}
        </button>
    </div>

    <div class="relative" style="height: 400px;">
        <canvas id="chart{{ ucfirst($type) }}"></canvas>
    </div>
</div>

@push('chart-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Safely parse the chart data
    let chartData;
    try {
        chartData = @json($chartData);
    } catch (e) {
        console.error('Error parsing chart data for {{ $type }}:', e);
        chartData = {};
    }

    // Ensure chartData has the expected structure
    if (!chartData || typeof chartData !== 'object') {
        chartData = {};
    }

    // Set up period labels based on the period
    const periodLabels = {
        'day': Array.from({length: 24}, (_, i) => `${i}:00`),
        'month': Array.from({length: new Date({{ date('Y') }}, {{ date('n') }}, 0).getDate()}, (_, i) => `${i + 1}`),
        'year': ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    };

    const labels = periodLabels['{{ $period }}'] || [];
    
    // Extract data for the specific type
    const typeData = chartData['{{ $type }}'] || {};
    
    // Prepare current data - ensure it's an array
    let currentData = [];
    if (typeData && Array.isArray(typeData)) {
        currentData = typeData;
    } else if (chartData['{{ $type === "electricity" ? "energy_consumed" : ($type === "gas" ? "gas_delivered" : "energy_produced") }}']) {
        currentData = chartData['{{ $type === "electricity" ? "energy_consumed" : ($type === "gas" ? "gas_delivered" : "energy_produced") }}'];
    }
    
    // Ensure currentData is an array and has the right length
    if (!Array.isArray(currentData)) {
        currentData = [];
    }
    
    // Fill with zeros if data is shorter than expected
    while (currentData.length < labels.length) {
        currentData.push(0);
    }

    // Chart configuration
    const config = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Current {{ ucfirst($type) }}',
                data: currentData,
                borderColor: '{{ $type === "electricity" ? "#3b82f6" : ($type === "gas" ? "#f59e0b" : "#10b981") }}',
                backgroundColor: '{{ $type === "electricity" ? "#3b82f6" : ($type === "gas" ? "#f59e0b" : "#10b981") }}20',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '{{ $title }}'
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '{{ $type === "gas" ? "m³" : "kWh" }}'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: '{{ ucfirst($period) }}'
                    }
                }
            }
        }
    };

    // Create chart
    const ctx = document.getElementById('chart{{ ucfirst($type) }}').getContext('2d');
    const chart{{ ucfirst($type) }} = new Chart(ctx, config);

    // Store chart instance globally for toggle function
    window.chart{{ ucfirst($type) }} = chart{{ ucfirst($type) }};
    window.chart{{ ucfirst($type) }}Data = {
        current: currentData,
        historical: [], // Will be populated when historical data is available
        showingHistorical: false
    };
});

// Toggle function for showing historical data
function toggleChart{{ ucfirst($type) }}() {
    const chart = window.chart{{ ucfirst($type) }};
    const data = window.chart{{ ucfirst($type) }}Data;
    
    if (!chart || !data) {
        console.error('Chart not initialized for {{ $type }}');
        return;
    }

    if (data.showingHistorical) {
        // Switch back to current data
        chart.data.datasets[0].data = data.current;
        chart.data.datasets[0].label = 'Current {{ ucfirst($type) }}';
        chart.data.datasets[0].borderColor = '{{ $type === "electricity" ? "#3b82f6" : ($type === "gas" ? "#f59e0b" : "#10b981") }}';
        chart.data.datasets[0].backgroundColor = '{{ $type === "electricity" ? "#3b82f6" : ($type === "gas" ? "#f59e0b" : "#10b981") }}20';
        document.getElementById('toggleChart{{ ucfirst($type) }}').textContent = '{{ $buttonLabel }}';
        data.showingHistorical = false;
    } else {
        // Switch to historical data (for now, using the same data as we don't have historical)
        // In the future, this should be replaced with actual historical data
        chart.data.datasets[0].data = data.historical.length > 0 ? data.historical : data.current;
        chart.data.datasets[0].label = 'Historical {{ ucfirst($type) }}';
        chart.data.datasets[0].borderColor = '#6b7280';
        chart.data.datasets[0].backgroundColor = '#6b728020';
        document.getElementById('toggleChart{{ ucfirst($type) }}').textContent = 'Show Current';
        data.showingHistorical = true;
    }
    
    chart.update();
}
</script>
@endpush