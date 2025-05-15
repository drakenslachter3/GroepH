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
    console.log("Loading chart for {{ $type }}");
    
    // Safely parse the chart data
    let chartData;
    try {
        chartData = @json($chartData);
        console.log("Chart data:", chartData);
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
        'month': Array.from({length: 31}, (_, i) => `${i + 1}`), // Fixed: Use max possible days
        'year': ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    };

    const labels = periodLabels['{{ $period }}'] || [];
    console.log("Labels:", labels);
    
    // Determine which field to use based on type
    const fieldName = '{{ $type === "electricity" ? "energy_consumed" : ($type === "gas" ? "gas_delivered" : "energy_produced") }}';
    console.log("Using field name:", fieldName);
    
    // Extract data from chartData
    let rawData = [];
    if (chartData[fieldName] && Array.isArray(chartData[fieldName])) {
        rawData = chartData[fieldName];
    } else if (chartData["{{ $type }}"] && Array.isArray(chartData["{{ $type }}"])) {
        // Fallback to type name if field name not found
        rawData = chartData["{{ $type }}"];
    } else {
        console.warn(`No ${fieldName} data found in chartData`);
    }
    
    console.log("Raw data:", rawData);
    
    // Ensure data is an array and has the right length
    if (!Array.isArray(rawData)) {
        rawData = [];
    }
    
    // Fill with zeros if data is shorter than expected
    while (rawData.length < labels.length) {
        rawData.push(0);
    }
    
    // Slice to match labels length if longer
    if (rawData.length > labels.length) {
        rawData = rawData.slice(0, labels.length);
    }
    
    // Create the chart with the raw data
    const ctx = document.getElementById('chart{{ ucfirst($type) }}').getContext('2d');
    const chart{{ ucfirst($type) }} = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '{{ ucfirst($type) }} Usage',
                data: rawData,
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
                    text: '{{ $title }} ({{ ucfirst($period) }})'
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toFixed(2) + ' {{ $type === "gas" ? "m³" : "kWh" }}';
                            return label;
                        }
                    }
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
    });

    // Store chart instance and data globally for toggle function
    window.chart{{ ucfirst($type) }} = chart{{ ucfirst($type) }};
    window.chart{{ ucfirst($type) }}Data = {
        current: rawData,
        historical: [],
        showingHistorical: false
    };
    
    // Load historical data if available
    if (chartData.historical_data && chartData.historical_data[fieldName]) {
        let historicalRaw = chartData.historical_data[fieldName];
        
        // Fill with zeros if data is shorter than expected
        while (historicalRaw.length < labels.length) {
            historicalRaw.push(0);
        }
        
        // Slice to match labels length if longer
        if (historicalRaw.length > labels.length) {
            historicalRaw = historicalRaw.slice(0, labels.length);
        }
        
        window.chart{{ ucfirst($type) }}Data.historical = historicalRaw;
    } else {
        // Create empty array matching the length of labels
        window.chart{{ ucfirst($type) }}Data.historical = Array(labels.length).fill(0);
    }
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
        chart.data.datasets[0].label = 'Current {{ ucfirst($type) }} Usage';
        chart.data.datasets[0].borderColor = '{{ $type === "electricity" ? "#3b82f6" : ($type === "gas" ? "#f59e0b" : "#10b981") }}';
        chart.data.datasets[0].backgroundColor = '{{ $type === "electricity" ? "#3b82f6" : ($type === "gas" ? "#f59e0b" : "#10b981") }}20';
        document.getElementById('toggleChart{{ ucfirst($type) }}').textContent = '{{ $buttonLabel }}';
        data.showingHistorical = false;
    } else {
        // Switch to historical data
        chart.data.datasets[0].data = data.historical.length > 0 ? data.historical : data.current;
        chart.data.datasets[0].label = 'Historical {{ ucfirst($type) }} Usage';
        chart.data.datasets[0].borderColor = '#6b7280';
        chart.data.datasets[0].backgroundColor = '#6b728020';
        document.getElementById('toggleChart{{ ucfirst($type) }}').textContent = 'Show Current';
        data.showingHistorical = true;
    }
    
    chart.update();
}
</script>
@endpush@props([
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
        'month': Array.from({length: 31}, (_, i) => `${i + 1}`), // Fixed: Use max possible days to avoid JS errors
        'year': ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    };

    const labels = periodLabels['{{ $period }}'] || [];
    
    // Determine which field to use based on type
    const fieldName = '{{ $type === "electricity" ? "energy_consumed" : ($type === "gas" ? "gas_delivered" : "energy_produced") }}';
    
    // Extract data for the specific type
    let rawData = [];
    if (chartData[fieldName] && Array.isArray(chartData[fieldName])) {
        rawData = chartData[fieldName];
    } else {
        console.warn(`No ${fieldName} data found, using empty array`);
    }
    
    // Ensure data is an array and has the right length
    if (!Array.isArray(rawData)) {
        rawData = [];
    }
    
    // Fill with last value or zeros if data is shorter than expected
    const lastValidValue = rawData.length > 0 ? rawData[rawData.length - 1] : 0;
    while (rawData.length < labels.length) {
        rawData.push(lastValidValue);
    }
    
    // IMPORTANT: Transform cumulative readings into consumption data
    // Calculate the differences between consecutive readings
    let consumptionData = [];
    if (rawData.length > 0) {
        consumptionData.push(0); // First value is always 0 (we don't have previous data)
        
        for (let i = 1; i < rawData.length; i++) {
            // Calculate difference, ensure it's not negative
            let diff = Math.max(0, rawData[i] - rawData[i-1]);
            consumptionData.push(diff);
        }
    } else {
        // If no data, fill with zeros
        consumptionData = Array(labels.length).fill(0);
    }
    
    // For hourly data, smooth out any extreme spikes (optional)
    if ('{{ $period }}' === 'day') {
        const nonZeroValues = consumptionData.filter(v => v > 0);
        const maxNormal = nonZeroValues.length > 0 ? 
            Math.max(...nonZeroValues) * 2 : 1; // Safe fallback if all zeros
            
        consumptionData = consumptionData.map(v => v > maxNormal ? maxNormal : v);
    }
    
    // Alternate approach: Show relative increase from first reading
    let relativeData = [];
    if (rawData.length > 0) {
        const baseline = rawData[0];
        relativeData = rawData.map(value => Math.max(0, value - baseline));
    } else {
        relativeData = Array(labels.length).fill(0);
    }
    
    // Decide which approach to use based on data characteristics
    // If all values are very close to each other, use relativeData
    const totalDiff = consumptionData.reduce((sum, val) => sum + val, 0);
    const maxDiff = Math.max(...consumptionData);
    
    // Safe default to consumptionData
    let dataToUse = consumptionData;
    
    // For debugging
    console.log(`${fieldName} - Raw data:`, rawData);
    console.log(`${fieldName} - Consumption data:`, consumptionData);
    console.log(`${fieldName} - Relative data:`, relativeData);
    console.log(`${fieldName} - Using:`, dataToUse);

    // Chart configuration
    const config = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '{{ ucfirst($type) }} Usage',
                data: dataToUse,
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
                    text: '{{ $title }} (Per {{ ucfirst($period) }})'
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toFixed(2) + ' {{ $type === "gas" ? "m³" : "kWh" }}';
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '{{ $type === "gas" ? "m³" : "kWh" }} per {{ $period }}'
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

    // Store chart instance and data globally for toggle function
    window.chart{{ ucfirst($type) }} = chart{{ ucfirst($type) }};
    window.chart{{ ucfirst($type) }}Data = {
        current: dataToUse,
        raw: rawData,
        historical: [], // Will be populated when historical data is available
        showingHistorical: false
    };
    
    // If there's historical data, prepare it using the same approach
    if (chartData.historical_data && chartData.historical_data[fieldName]) {
        let historicalRaw = chartData.historical_data[fieldName];
        
        // Initialize array
        let historicalConsumption = [];
        
        if (historicalRaw.length > 0) {
            historicalConsumption.push(0); // First element is 0
            
            for (let i = 1; i < historicalRaw.length; i++) {
                let diff = Math.max(0, historicalRaw[i] - historicalRaw[i-1]);
                historicalConsumption.push(diff);
            }
        } else {
            historicalConsumption = Array(labels.length).fill(0);
        }
        
        // Apply the same smoothing if needed
        if ('{{ $period }}' === 'day') {
            const nonZeroValues = historicalConsumption.filter(v => v > 0);
            const maxNormal = nonZeroValues.length > 0 ? 
                Math.max(...nonZeroValues) * 2 : 1;
                
            historicalConsumption = historicalConsumption.map(v => v > maxNormal ? maxNormal : v);
        }
        
        // Calculate relative data safely
        let historicalRelative = [];
        if (historicalRaw.length > 0) {
            const baseline = historicalRaw[0];
            historicalRelative = historicalRaw.map(value => Math.max(0, value - baseline));
        } else {
            historicalRelative = Array(labels.length).fill(0);
        }
        
        // Use the same approach as current data for consistency
        window.chart{{ ucfirst($type) }}Data.historical = historicalConsumption;
    } else {
        window.chart{{ ucfirst($type) }}Data.historical = Array(labels.length).fill(0);
    }
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
        chart.data.datasets[0].label = 'Current {{ ucfirst($type) }} Usage';
        chart.data.datasets[0].borderColor = '{{ $type === "electricity" ? "#3b82f6" : ($type === "gas" ? "#f59e0b" : "#10b981") }}';
        chart.data.datasets[0].backgroundColor = '{{ $type === "electricity" ? "#3b82f6" : ($type === "gas" ? "#f59e0b" : "#10b981") }}20';
        document.getElementById('toggleChart{{ ucfirst($type) }}').textContent = '{{ $buttonLabel }}';
        data.showingHistorical = false;
    } else {
        // Switch to historical data
        chart.data.datasets[0].data = data.historical.length > 0 ? data.historical : data.current;
        chart.data.datasets[0].label = 'Historical {{ ucfirst($type) }} Usage';
        chart.data.datasets[0].borderColor = '#6b7280';
        chart.data.datasets[0].backgroundColor = '#6b728020';
        document.getElementById('toggleChart{{ ucfirst($type) }}').textContent = 'Show Current';
        data.showingHistorical = true;
    }
    
    chart.update();
}
</script>
@endpush