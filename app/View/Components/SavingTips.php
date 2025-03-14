<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class SavingTips extends Component
{
    /**
     * Besparingstips voor de gebruiker
     */
    public $tips;
    
    /**
     * Maak een nieuwe component instantie.
     */
    public function __construct($tips)
    {
        $this->tips = $tips;
    }

    /**
     * Bepaal de view / inhoud die de component representeert.
     */
    public function render(): View
    {
        return view('components.saving-tips');
    }
}