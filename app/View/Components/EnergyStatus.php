<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergyStatus extends Component
{
    /**
     * Type energie (Elektriciteit, Gas)
     */
    public $type;
    
    /**
     * Huidig verbruik
     */
    public $usage;
    
    /**
     * Target verbruik
     */
    public $target;
    
    /**
     * Huidige kosten
     */
    public $cost;
    
    /**
     * Percentage t.o.v. target
     */
    public $percentage;
    
    /**
     * Status (goed, waarschuwing, kritiek)
     */
    public $status;
    
    /**
     * Eenheid (kWh, m³)
     */
    public $unit;
    
    /**
     * Maak een nieuwe component instantie.
     */
    public function __construct($type, $usage, $target, $cost, $percentage, $status, $unit)
    {
        $this->type = $type;
        $this->usage = $usage;
        $this->target = $target;
        $this->cost = $cost;
        $this->percentage = $percentage;
        $this->status = $status;
        $this->unit = $unit;
    }

    /**
     * Bepaal de view / inhoud die de component representeert.
     */
    public function render(): View
    {
        return view('components.energy-status');
    }
}