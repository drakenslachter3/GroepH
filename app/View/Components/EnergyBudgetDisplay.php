<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\DB;
use App\Models\EnergyBudget;

class EnergyBudgetDisplay extends Component
{
    /**
     * The energy budget data.
     *
     * @var object
     */
    public $budget;
    
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
     * Load the most recent energy budget from database.
     *
     * @return void
     */
    protected function loadEnergyBudget()
    {
        // Get the energy budget with the highest ID
        $this->budget = EnergyBudget::latest('id')->first();
    }
    
    /**
     * Calculate gas usage percentage.
     *
     * @return float
     */
    public function gasPercentage()
    {
        // This would be calculated from actual usage data
        // For demonstration, we'll return 65%
        return 65;
    }
    
    /**
     * Calculate electricity usage percentage.
     *
     * @return float
     */
    public function electricityPercentage()
    {
        // This would be calculated from actual usage data
        // For demonstration, we'll return 70%
        return 70;
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