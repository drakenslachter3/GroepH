<!-- resources/views/components/energy-budget-display.blade.php -->
<div class="energy-budget-display">
    @if($budget)
        <div class="budget-header">
            <h2>Energy Budget {{ $budget->year }}</h2>
            <p class="last-updated">Last updated: {{ $budget->updated_at->format('M d, Y') }}</p>
        </div>
        
        <div class="budget-cards">
            <div class="budget-card">
                <div class="card-header">
                    <h3>Gas Consumption</h3>
                    <span class="card-subtitle">Yearly Target</span>
                </div>
                
                <div class="target-values">
                    <div class="target-value">
                        <span class="value">{{ number_format($budget->gas_target_m3, 2) }}</span>
                        <span class="unit">m³</span>
                    </div>
                    <div class="target-value">
                        <span class="value">€{{ number_format($budget->gas_target_euro, 2) }}</span>
                        <span class="unit">euro</span>
                    </div>
                </div>
                
                <div class="progress-container">
                    <div class="progress-bar" style="width: {{ $gasPercentage }}%"></div>
                </div>
                <div class="progress-label">
                    <span>{{ $gasPercentage }}% of yearly target used</span>
                </div>
            </div>
            
            <div class="budget-card">
                <div class="card-header">
                    <h3>Electricity Consumption</h3>
                    <span class="card-subtitle">Yearly Target</span>
                </div>
                
                <div class="target-values">
                    <div class="target-value">
                        <span class="value">{{ number_format($budget->electricity_target_kwh, 2) }}</span>
                        <span class="unit">kWh</span>
                    </div>
                    <div class="target-value">
                        <span class="value">€{{ number_format($budget->electricity_target_euro, 2) }}</span>
                        <span class="unit">euro</span>
                    </div>
                </div>
                
                <div class="progress-container">
                    <div class="progress-bar" style="width: {{ $electricityPercentage }}%"></div>
                </div>
                <div class="progress-label">
                    <span>{{ $electricityPercentage }}% of yearly target used</span>
                </div>
            </div>
        </div>
        
        <div class="budget-summary">
            <h3>Total Energy Budget</h3>
            <div class="summary-total">
                <span class="total-label">Total Budget:</span>
                <span class="total-amount">
                    €{{ number_format($budget->gas_target_euro + $budget->electricity_target_euro, 2) }}
                </span>
            </div>
        </div>
    @else
        <div class="no-data">
            <p>No energy budget data available.</p>
        </div>
    @endif
</div>