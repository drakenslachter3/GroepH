<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergyChart extends Component
{
    /** Chart title */
    public $title;

    /** Energy type (e.g. electricity, gas) */
    public $type;

    /** Unit of measurement (e.g. kWh, m³) */
    public $unit;

    /** Time period (day, month, year) */
    public $period;

    /** Selected date */
    public $date;

    /** Label for the toggle button */
    public $buttonLabel;

    /** Color for the button */
    public $buttonColor;

    /** Chart data */
    public $chartData;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $title,
        $type,
        $unit,
        $period,
        $date,
        $buttonLabel,
        $buttonColor,
        $chartData
    ) {
        $this->title = $title;
        $this->type = $type;
        $this->unit = $unit;
        $this->period = $period;
        $this->date = $date;
        $this->buttonLabel = $buttonLabel;
        $this->buttonColor = $buttonColor;
        $this->chartData = $chartData;
    }

    /**
     * Return the component view.
     */
    public function render(): View
    {
        return view('components.dashboard.energy-chart');
    }
}
