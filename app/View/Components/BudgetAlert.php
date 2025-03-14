<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

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
     * Maak een nieuwe component instantie.
     */
    public function __construct($electricityPercentage, $gasPercentage, $threshold)
    {
        $this->electricityPercentage = $electricityPercentage;
        $this->gasPercentage = $gasPercentage;
        $this->threshold = $threshold;
        $this->showWarning = $electricityPercentage > $threshold || $gasPercentage > $threshold;
    }

    /**
     * Bepaal de view / inhoud die de component representeert.
     */
    public function render(): View
    {
        return view('components.budget-alert');
    }
}