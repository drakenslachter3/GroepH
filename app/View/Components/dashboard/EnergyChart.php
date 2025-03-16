<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergyChart extends Component
{
    /**
     * Type energie (electricity, gas)
     */
    public $type;
    
    /**
     * Titel van de grafiek
     */
    public $title;
    
    /**
     * Label voor de toggle knop
     */
    public $buttonLabel;
    
    /**
     * Kleur voor de knop
     */
    public $buttonColor;
    
    /**
     * Grafiek data
     */
    public $chartData;
    
    /**
     * Periode (day, month, year)
     */
    public $period;

    /**
     * Maak een nieuwe component instantie.
     */
    public function __construct($type, $title, $buttonLabel, $buttonColor, $chartData, $period)
    {
        $this->type = $type;
        $this->title = $title;
        $this->buttonLabel = $buttonLabel;
        $this->buttonColor = $buttonColor;
        $this->chartData = $chartData;
        $this->period = $period;
    }

    /**
     * Bepaal de view / inhoud die de component representeert.
     */
    public function render(): View
    {
        return view('components.dashboard.energy-chart');
    }
}