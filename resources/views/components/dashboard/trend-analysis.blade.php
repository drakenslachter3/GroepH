@props(['electricityData', 'gasData'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 dark:bg-gray-800">
    <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Trendanalyse</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium text-gray-700 dark:text-gray-200 mb-2">Langetermijnverbruik (Elektriciteit)</h4>
                <div style="height: 250px;">
                    <canvas id="electricityTrendChart"></canvas>
                </div>
            </div>
            <div>
                <h4 class="font-medium text-gray-700 dark:text-gray-200 mb-2">Langetermijnverbruik (Gas)</h4>
                <div style="height: 250px;">
                    <canvas id="gasTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('trend-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') return;
        
        // Get theme colors
        const getThemeColors = () => {
            const isDark = document.documentElement.classList.contains('dark') || 
                          document.querySelector('html').classList.contains('dark') ||
                          window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            return {
                textColor: isDark ? '#FFFFFF' : '#000000',
                gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
            };
        };
        
        // Get initial colors
        const { textColor, gridColor } = getThemeColors();
        
        // Data voor de trends
        let electricityTrendData = {
            thisYear: [210, 195, 180, 170, 165, 168, 172, 175, 168, 182, 190, 200],
            lastYear: [230, 220, 200, 185, 180, 182, 190, 195, 185, 200, 210, 225]
        };

        let gasTrendData = {
            thisYear: [120, 115, 90, 65, 40, 25, 20, 20, 35, 70, 100, 110],
            lastYear: [130, 125, 100, 70, 45, 30, 25, 25, 40, 75, 110, 120]
        };

        // Overschrijf met echte data als beschikbaar
        @if(isset($electricityData))
            electricityTrendData = @json($electricityData);
        @endif

        @if(isset($gasData))
            gasTrendData = @json($gasData);
        @endif
        
        // Maandlabels
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Elektriciteit Trend Chart
        const electricityTrendCtx = document.getElementById('electricityTrendChart').getContext('2d');
        const electricityTrendChart = new Chart(electricityTrendCtx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Dit Jaar',
                        data: electricityTrendData.thisYear,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Vorig Jaar',
                        data: electricityTrendData.lastYear,
                        borderColor: 'rgb(107, 114, 128)',
                        borderDash: [5, 5],
                        backgroundColor: 'rgba(107, 114, 128, 0)',
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    },
                    legend: {
                        labels: {
                            color: textColor
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'kWh per maand',
                            color: textColor
                        },
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    }
                }
            }
        });
        
        // Gas Trend Chart
        const gasTrendCtx = document.getElementById('gasTrendChart').getContext('2d');
        const gasTrendChart = new Chart(gasTrendCtx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Dit Jaar',
                        data: gasTrendData.thisYear,
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Vorig Jaar',
                        data: gasTrendData.lastYear,
                        borderColor: 'rgb(107, 114, 128)',
                        borderDash: [5, 5],
                        backgroundColor: 'rgba(107, 114, 128, 0)',
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    },
                    legend: {
                        labels: {
                            color: textColor
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'm³ per maand',
                            color: textColor
                        },
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor
                        }
                    }
                }
            }
        });
        
        // Function to update chart colors based on theme
        const updateChartColors = () => {
            const { textColor, gridColor } = getThemeColors();
            
            // Update electricity chart
            electricityTrendChart.options.scales.x.ticks.color = textColor;
            electricityTrendChart.options.scales.x.grid.color = gridColor;
            electricityTrendChart.options.scales.y.title.color = textColor;
            electricityTrendChart.options.scales.y.ticks.color = textColor;
            electricityTrendChart.options.scales.y.grid.color = gridColor;
            electricityTrendChart.options.plugins.legend.labels.color = textColor;
            electricityTrendChart.update();
            
            // Update gas chart
            gasTrendChart.options.scales.x.ticks.color = textColor;
            gasTrendChart.options.scales.x.grid.color = gridColor;
            gasTrendChart.options.scales.y.title.color = textColor;
            gasTrendChart.options.scales.y.ticks.color = textColor;
            gasTrendChart.options.scales.y.grid.color = gridColor;
            gasTrendChart.options.plugins.legend.labels.color = textColor;
            gasTrendChart.update();
        };
        
        // Watch for theme changes
        const themeWatcher = () => {
            // Use matchMedia for system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateChartColors);
            
            // Use MutationObserver for class changes on HTML element
            const darkModeObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        updateChartColors();
                    }
                });
            });
            
            darkModeObserver.observe(document.documentElement, { attributes: true });
        };
        
        themeWatcher();
    });
</script>
@endpush