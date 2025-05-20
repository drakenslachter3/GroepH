<?php
namespace App\View\Components;

use Illuminate\View\Component;

class WidgetHeading extends Component
{
    public string $type;
    public ?string $date;
    public ?string $period;

    public function __construct(string $type, ?string $date = null, ?string $period = null)
    {
        $this->type = $type;
        $this->date = $date;
        $this->period = $period;
    }

    public function render()
    {
        return view('components.etc.widget-heading');
    }
}
