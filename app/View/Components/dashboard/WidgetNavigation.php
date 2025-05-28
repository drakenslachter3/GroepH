<?php

namespace App\View\Components\Dashboard;

use Illuminate\View\Component;

class WidgetNavigation extends Component
{
    /** Text for the "previous" button */
    public $previousText;

    /** Text for the "next" button */
    public $nextText;

    /** Whether to show the "previous" button */
    public $showPrevious;

    /** Whether to show the "next" button */
    public $showNext;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $previousText = 'Ga naar vorige widget',
        $nextText = 'Ga naar volgende widget',
        $showPrevious = false,
        $showNext = false
    ) {
        $this->previousText = $previousText;
        $this->nextText = $nextText;
        $this->showPrevious = $showPrevious;
        $this->showNext = $showNext;
    }

    /**
     * Return the component view.
     */
    public function render()
    {
        return view('components.dashboard.widget-navigation');
    }
}
