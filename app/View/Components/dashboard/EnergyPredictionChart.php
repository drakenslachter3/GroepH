<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergyPredictionChart extends Component
{
    public $title;
    public $currentData;
    public $budgetData;
    public $type;
    public $period;
    public $date;
    public $percentage;
    public $confidence;

    public function __construct(
        string $title,
        array $currentData, 
        array $budgetData, 
        string $type, 
        string $period,
        string $date,
        float $percentage,
        int $confidence
    ) {
        $this->title = $title;
        $this->currentData = $currentData;
        $this->budgetData = $budgetData;
        $this->type = $type;
        $this->date = $date;
        $this->period = $period;
        $this->percentage = $percentage;
        $this->confidence = $confidence;
    }

    public function render(): View
    {
        return view('components.dashboard.energy-prediction-chart');
    }
}