<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergyChart extends Component
{
    public $title;
    public $type;
    public $unit;
    public $period;
    public $date;
    public $buttonLabel;
    public $buttonColor;
    public $chartData;

    public function __construct($title, $type, $unit, $period, $date, $buttonLabel, $buttonColor, $chartData)
    {
        $this->title = $title;
        $this->type = $type;
        $this->unit = $unit;
        $this->period = $period;
        $this->date = $date;
        $this->buttonLabel = $buttonLabel;
        $this->buttonColor = $buttonColor;
        $this->chartData = $chartData;
    }

    public function render(): View
    {
        return view('components.dashboard.energy-chart');
    }
}