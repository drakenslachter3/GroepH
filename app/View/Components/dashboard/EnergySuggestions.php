<?php

namespace App\View\Components\dashboard;

use Illuminate\View\Component;
use Illuminate\View\View;

class EnergySuggestions extends Component
{
    public $title;
    public $usagePattern;
    public $housingType;
    public $season;

    public function __construct($title, $usagePattern = null, $housingType = 'tussenwoning', $season = null)
    {
        $this->title = $title;
        $this->usagePattern = $usagePattern;
        $this->housingType = $housingType;
        
        // Bepaal seizoen als het niet is meegegeven
        if ($season === null) {
            $month = (int) date('n');
            if ($month >= 3 && $month <= 5) {
                $this->season = 'lente';
            } elseif ($month >= 6 && $month <= 8) {
                $this->season = 'zomer';
            } elseif ($month >= 9 && $month <= 11) {
                $this->season = 'herfst';
            } else {
                $this->season = 'winter';
            }
        } else {
            $this->season = $season;
        }
    }

    /**
     * Bepaal de view / inhoud die de component representeert.
     */
    public function render(): View
    {
        return view('components.dashboard.energy-suggestions');
    }
}