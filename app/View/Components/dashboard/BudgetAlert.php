<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;
use App\Models\SmartMeter;
use App\Models\EnergyBudget;
use App\Models\UserGridLayout;

class BudgetAlert extends Component
{
    /**
     * Percentage elektriciteit van het budget
     */
    public $electricityPercentage;
    
    /**
     * Percentage gas van het budget
     */
    public $gasPercentage;
    
    /**
     * Drempelwaarde voor het tonen van de waarschuwing
     */
    public $threshold;
    
    /**
     * Bepaal of een waarschuwing moet worden getoond
     */
    public $showWarning;
    
    /**
     * The selected smart meter
     */
    public $smartMeter;
    
    /**
     * The energy budget for the selected meter
     */
    public $budget;
    
    /**
     * Maak een nieuwe component instantie.
     */
    public function __construct($electricityPercentage = null, $gasPercentage = null, $threshold = 80)
    {
        $this->threshold = $threshold;
        
        // Get the selected smart meter
        $this->getSelectedSmartMeter();
        
        // If percentages are not provided, calculate them from the selected meter's budget
        if ($electricityPercentage === null || $gasPercentage === null) {
            $this->calculatePercentagesFromMeter();
        } else {
            $this->electricityPercentage = $electricityPercentage;
            $this->gasPercentage = $gasPercentage;
        }
        
        $this->showWarning = $this->electricityPercentage > $threshold || $this->gasPercentage > $threshold;
    }
    
    /**
     * Get the selected smart meter for the current user
     */
    private function getSelectedSmartMeter()
    {
        // Get selected meter from session
        $selectedMeterId = session('selected_meter_id');
        
        if ($selectedMeterId) {
            $this->smartMeter = SmartMeter::where('meter_id', $selectedMeterId)
                ->where('account_id', auth()->id())
                ->first();
        }
        
        // Fallback to grid layout selection
        if (!$this->smartMeter) {
            $layout = UserGridLayout::where('user_id', auth()->id())->first();
            if ($layout && $layout->selected_smartmeter) {
                $this->smartMeter = SmartMeter::find($layout->selected_smartmeter);
            }
        }
        
        // Final fallback to first available meter
        if (!$this->smartMeter) {
            $this->smartMeter = SmartMeter::where('account_id', auth()->id())
                ->where('active', true)
                ->first();
        }
        
        // Load the budget for this meter
        if ($this->smartMeter) {
            $this->budget = EnergyBudget::where('smart_meter_id', $this->smartMeter->id)
                ->where('year', date('Y'))
                ->with(['monthlyBudgets' => function($query) {
                    $query->orderBy('month');
                }])
                ->first();
        }
    }
    
    /**
     * Calculate usage percentages based on the selected meter's budget and current usage
     */
    private function calculatePercentagesFromMeter()
    {
        $this->electricityPercentage = 0;
        $this->gasPercentage = 0;
        
        if (!$this->smartMeter || !$this->budget) {
            return;
        }
        
        $currentMonth = date('n');
        $monthlyBudget = $this->budget->monthlyBudgets->where('month', $currentMonth)->first();
        
        if (!$monthlyBudget) {
            return;
        }
        
        // Calculate electricity percentage if meter measures electricity
        if ($this->smartMeter->measures_electricity && $monthlyBudget->electricity_target_kwh > 0) {
            $currentElectricityUsage = $this->getCurrentElectricityUsage();
            $this->electricityPercentage = ($currentElectricityUsage / $monthlyBudget->electricity_target_kwh) * 100;
        }
        
        // Calculate gas percentage if meter measures gas
        if ($this->smartMeter->measures_gas && $monthlyBudget->gas_target_m3 > 0) {
            $currentGasUsage = $this->getCurrentGasUsage();
            $this->gasPercentage = ($currentGasUsage / $monthlyBudget->gas_target_m3) * 100;
        }
    }
    
    /**
     * Get current electricity usage for the selected meter
     * TODO: Replace with actual meter reading data
     */
    private function getCurrentElectricityUsage()
    {
        // Placeholder - in real implementation, get from InfluxDB or meter readings
        $daysInMonth = date('t');
        $currentDay = date('j');
        $progress = $currentDay / $daysInMonth;
        
        // Simulate current usage based on progress through month
        $monthlyBudget = $this->budget->monthlyBudgets->where('month', date('n'))->first();
        if ($monthlyBudget) {
            return $monthlyBudget->electricity_target_kwh * $progress * (0.9 + (mt_rand(0, 20) / 100));
        }
        
        return 0;
    }
    
    /**
     * Get current gas usage for the selected meter
     * TODO: Replace with actual meter reading data
     */
    private function getCurrentGasUsage()
    {
        // Placeholder - in real implementation, get from InfluxDB or meter readings
        $daysInMonth = date('t');
        $currentDay = date('j');
        $progress = $currentDay / $daysInMonth;
        
        // Simulate current usage based on progress through month
        $monthlyBudget = $this->budget->monthlyBudgets->where('month', date('n'))->first();
        if ($monthlyBudget) {
            return $monthlyBudget->gas_target_m3 * $progress * (0.9 + (mt_rand(0, 20) / 100));
        }
        
        return 0;
    }

    /**
     * Bepaal de view / inhoud die de component representeert.
     */
    public function render(): View
    {
        return view('components.dashboard.budget-alert');
    }
}