<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class TrendAnalysis extends Component
{
    /**
     * Gegevens voor de elektriciteit trend
     */
    public $electricityData;
    
    /**
     * Gegevens voor de gas trend
     */
    public $gasData;
    
    /**
     * Maak een nieuwe component instantie.
     */
    public function __construct($electricityData, $gasData)
    {
        $this->electricityData = $electricityData;
        $this->gasData = $gasData;
    }

    /**
     * Bepaal de view / inhoud die de component representeert.
     */
    public function render(): View
    {
        return view('components.dashboard.trend-analysis');
    }
}