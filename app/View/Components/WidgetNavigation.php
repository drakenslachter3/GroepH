<?php

namespace App\View\Components;

use Illuminate\View\Component;

class WidgetNavigation extends Component
{
    /**
     * The previous button text.
     *
     * @var string
     */
    public $previousText;
    
    /**
     * The next button text.
     *
     * @var string
     */
    public $nextText;
    
    /**
     * Whether to show the previous button.
     *
     * @var bool
     */
    public $showPrevious;
    
    /**
     * Whether to show the next button.
     *
     * @var bool
     */
    public $showNext;

    /**
     * Create a new component instance.
     *
     * @param string $previousText
     * @param string $nextText
     * @param bool $showPrevious
     * @param bool $showNext
     * @return void
     */
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

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.etc.widget-navigation');
    }
}