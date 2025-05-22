<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergySuggestions extends Component
{
    /** Chart title */
    public $title;

    /** Usage pattern */
    public $usagePattern;

    /** Housing type, default is 'tussenwoning' (terraced house) */
    public $housingType;

    /** Season (spring, summer, autumn, winter) */
    public $season;

    /**
     * Create a new component instance.
     */
    public function __construct($title, $usagePattern = null, $housingType = 'tussenwoning', $season = null)
    {
        $this->title = $title;
        $this->usagePattern = $usagePattern;
        $this->housingType = $housingType;

        // Determine season if not provided
        if ($season === null) {
            $month = (int) date('n');
            if ($month >= 3 && $month <= 5) {
                $this->season = 'spring';
            } elseif ($month >= 6 && $month <= 8) {
                $this->season = 'summer';
            } elseif ($month >= 9 && $month <= 11) {
                $this->season = 'autumn';
            } else {
                $this->season = 'winter';
            }
        } else {
            $this->season = $season;
        }
    }

    /**
     * Return the component view.
     */
    public function render(): View
    {
        return view('components.dashboard.energy-suggestions');
    }
}
