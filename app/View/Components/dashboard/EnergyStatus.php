<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergyStatus extends Component
{
    /** Chart title */
    public $title;

    /** Energy type (e.g. electricity, gas) */
    public $type;

    /** Current usage value */
    public $usage;

    /** Target value */
    public $target;

    /** Cost value */
    public $cost;

    /** Percentage of target used */
    public $percentage;

    /** Current status (e.g. alert, ok) */
    public $status;

    /** Unit of measurement (e.g. kWh, m³) */
    public $unit;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $type,
        $title,
        $usage,
        $target,
        $cost,
        $percentage,
        $status,
        $unit
    ) {
        $this->type = $type;
        $this->title = $title;
        $this->usage = $usage;
        $this->target = $target;
        $this->cost = $cost;
        $this->percentage = $percentage;
        $this->status = $status;
        $this->unit = $unit;
    }

    /**
     * Return the component view.
     */
    public function render(): View
    {
        return view('components.dashboard.energy-status');
    }
}
