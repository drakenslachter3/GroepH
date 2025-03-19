<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class HistoricalComparison extends Component
{
    /**
     * Gegevens van afgelopen week
     */
    public $weekData;
    
    /**
     * Gegevens van afgelopen maand
     */
    public $monthData;
    
    /**
     * Gegevens van dezelfde periode vorig jaar
     */
    public $yearComparisonData;
    
    /**
     * Maak een nieuwe component instantie.
     */
    public function __construct($weekData, $monthData, $yearComparisonData)
    {
        $this->weekData = $weekData;
        $this->monthData = $monthData;
        $this->yearComparisonData = $yearComparisonData;
    }

    /**
     * Bepaal de view / inhoud die de component representeert.
     */
    public function render(): View
    {
        return view('components.dashboard.historical-comparison');
    }
}