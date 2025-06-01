<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\DB;
use App\Models\EnergyBudget;
use App\Models\SmartMeter;
use App\Models\UserGridLayout;

class EnergyBudgetDisplay extends Component
{
    /**
     * The energy budget data.
     *
     * @var object
     */
    public $budget;
    
    /**
     * The selected smart meter
     *
     * @var SmartMeter
     */
    public $smartMeter;
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->loadEnergyBudget();
    }
    
    /**
     * Load the energy budget for the selected smart meter from database.
     *
     * @return void
     */
    protected function loadEnergyBudget()
    {
        // Get the selected smart meter ID from session or user grid layout
        $selectedMeterId = session('selected_meter_id');
        
        if (!$selectedMeterId) {
            // Fallback to user's grid layout
            $layout = UserGridLayout::where('user_id', auth()->id())->first();
            if ($layout && $layout->selected_smartmeter) {
                $this->smartMeter = SmartMeter::find($layout->selected_smartmeter);
            } else {
                // Get first available smart meter for user
                $this->smartMeter = SmartMeter::where('account_id', auth()->id())
                    ->where('active', true)
                    ->first();
            }
        } else {
            // Get smart meter by meter_id from session
            $this->smartMeter = SmartMeter::where('meter_id', $selectedMeterId)->first();
        }
        
        if ($this->smartMeter) {
            // Get the energy budget for this specific meter
            $this->budget = EnergyBudget::where('smart_meter_id', $this->smartMeter->id)
                ->where('year', date('Y'))
                ->with(['monthlyBudgets' => function($query) {
                    $query->orderBy('month');
                }])
                ->first();
        }
    }
    
    /**
     * Calculate gas usage percentage based on smart meter budget.
     *
     * @return float
     */
    public function gasPercentage()
    {
        if (!$this->budget || !$this->smartMeter || !$this->smartMeter->measures_gas) {
            return 0;
        }
        
        // Get current month's budget
        $currentMonth = date('n');
        $monthlyBudget = $this->budget->monthlyBudgets->where('month', $currentMonth)->first();
        
        if ($monthlyBudget && $monthlyBudget->gas_target_m3 > 0) {
            // This would be calculated from actual usage data for this specific meter
            // For demonstration, we'll return a calculated percentage
            $currentUsage = $this->getCurrentGasUsage();
            return ($currentUsage / $monthlyBudget->gas_target_m3) * 100;
        }
        
        return 0;
    }
    
    /**
     * Calculate electricity usage percentage based on smart meter budget.
     *
     * @return float
     */
    public function electricityPercentage()
    {
        if (!$this->budget || !$this->smartMeter || !$this->smartMeter->measures_electricity) {
            return 0;
        }
        
        // Get current month's budget
        $currentMonth = date('n');
        $monthlyBudget = $this->budget->monthlyBudgets->where('month', $currentMonth)->first();
        
        if ($monthlyBudget && $monthlyBudget->electricity_target_kwh > 0) {
            // This would be calculated from actual usage data for this specific meter
            // For demonstration, we'll return a calculated percentage
            $currentUsage = $this->getCurrentElectricityUsage();
            return ($currentUsage / $monthlyBudget->electricity_target_kwh) * 100;
        }
        
        return 0;
    }
    
    /**
     * Get current gas usage for the selected meter (placeholder for real implementation)
     *
     * @return float
     */
    private function getCurrentGasUsage()
    {
        // In a real implementation, this would query the meter readings
        // or InfluxDB data for the current month's usage
        if (!$this->smartMeter) {
            return 0;
        }
        
        // Placeholder calculation - replace with actual meter data
        $daysInMonth = date('t');
        $currentDay = date('j');
        $dailyAverage = 4.2; // m³ per day average
        
        return $dailyAverage * $currentDay;
    }
    
    /**
     * Get current electricity usage for the selected meter (placeholder for real implementation)
     *
     * @return float
     */
    private function getCurrentElectricityUsage()
    {
        // In a real implementation, this would query the meter readings
        // or InfluxDB data for the current month's usage
        if (!$this->smartMeter) {
            return 0;
        }
        
        // Placeholder calculation - replace with actual meter data
        $daysInMonth = date('t');
        $currentDay = date('j');
        $dailyAverage = 8.5; // kWh per day average
        
        return $dailyAverage * $currentDay;
    }
    
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.energy-budget-display');
    }
}