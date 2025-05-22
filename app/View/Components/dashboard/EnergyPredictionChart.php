<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergyPredictionChart extends Component
{
    /** Chart title */
    public $title;

    /** Actual measured data */
    public $currentData;

    /** Budget or forecast data */
    public $budgetData;

    /** Energy type (e.g. electricity, gas) */
    public $type;

    /** Period shown (day, month, year) */
    public $period;

    /** Selected date */
    public $date;

    /** Difference between actual and budget (as % value) */
    public $percentage;

    /** Confidence level in prediction (0–100) */
    public $confidence;

    /**
     * Create a new component instance.
     */
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
        $this->period = $period;
        $this->date = $date;
        $this->percentage = $percentage;
        $this->confidence = $confidence;
    }

    /**
     * Return the component view.
     */
    public function render(): View
    {
        return view('components.dashboard.energy-prediction-chart');
    }
}
