<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class DateSelector extends Component
{
    /**
     * Geselecteerde periode (day, month, year)
     */
    public $period;
    
    /**
     * Geselecteerde datum
     */
    public $date;
    
    /**
     * Geselecteerde woningtype
     */
    public $housingType;
    
    /**
     * Maak een nieuwe component instantie.
     *
     * @param string $period
     * @param string $date
     * @param string $housingType
     * @return void
     */
    public function __construct($period, $date, $housingType)
    {
        $this->period = $period;
        $this->date = $date;
        $this->housingType = $housingType;
    }

    /**
     * Bepaal de view / inhoud die de component representeert.
     */
    public function render(): View
    {
        return view('components.date-selector');
    }
}