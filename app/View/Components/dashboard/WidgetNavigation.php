<?php

namespace App\View\Components\Dashboard;

use Illuminate\View\Component;

class WidgetNavigation extends Component
{
    public $previousText;
    public $nextText;
    public $showPrevious;
    public $showNext;

    public function __construct(
        string $previousText = 'Ga naar vorige widget',
        string $nextText = 'Ga naar volgende widget',
        bool $showPrevious = false,
        bool $showNext = false
    ) {
        $this->previousText = $previousText;
        $this->nextText = $nextText;
        $this->showPrevious = $showPrevious;
        $this->showNext = $showNext;
    }

    public function render()
    {
        return view('components.dashboard.widget-navigation');
    }
}