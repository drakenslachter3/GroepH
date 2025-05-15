<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergyPredictionChart extends Component
{
    /**
     * Current energy usage data
     */
    public $currentData;
    
    /**
     * Budget target data
     */
    public $budgetData;
    
    /**
     * Type of energy (electricity or gas)
     */
    public $type;
    
    /**
     * Period (day, month, year)
     */
    public $period;
    
    /**
     * Current percentage of budget used
     */
    public $percentage;
    
    /**
     * Confidence level for predictions (0-100)
     */
    public $confidence;
    
    /**
     * Create a new component instance.
     */
    public function __construct(
        array $currentData, 
        array $budgetData, 
        string $type = 'electricity', 
        string $period = 'year',
        float $percentage = 0,
        int $confidence = 80
    ) {
        $this->currentData = $currentData;
        $this->budgetData = $budgetData;
        $this->type = $type;
        $this->period = $period;
        $this->percentage = $percentage;
        $this->confidence = $confidence;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.dashboard.energy-prediction-chart');
    }
}