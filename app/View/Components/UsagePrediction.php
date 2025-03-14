<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class UsagePrediction extends Component
{
    /**
     * Elektriciteitsgegevens voor voorspelling
     */
    public $electricityData;
    
    /**
     * Gasgegevens voor voorspelling
     */
    public $gasData;
    
    /**
     * Huidige periode (day, month, year)
     */
    public $period;
    
    /**
     * Maak een nieuwe component instantie.
     */
    public function __construct($electricityData, $gasData, $period)
    {
        $this->electricityData = $electricityData;
        $this->gasData = $gasData;
        $this->period = $period;
    }

    /**
     * Bepaal de view / inhoud die de component representeert.
     */
    public function render(): View
    {
        return view('components.usage-prediction');
    }
}