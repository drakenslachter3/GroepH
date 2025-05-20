<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergyStatus extends Component
{
    public $title;
    public $type;
    public $usage;
    public $target;
    public $cost;
    public $percentage;
    public $status;
    public $unit;
    
    public function __construct($type, $title, $usage, $target, $cost, $percentage, $status, $unit)
    {
        $this->type = $type;
        $this->title = $title;
        $this->usage = $usage;
        $this->target = $target;
        $this->cost = $cost;
        $this->percentage = $percentage;
        $this->status = $status;
        $this->unit = $unit;
    }

    public function render(): View
    {
        return view('components.dashboard.energy-status');
    }
}