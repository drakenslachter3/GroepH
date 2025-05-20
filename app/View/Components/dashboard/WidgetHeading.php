<?php
namespace App\View\Components\Dashboard;

use Illuminate\View\Component;

class WidgetHeading extends Component
{
    public string $title;
    public ?string $type;
    public ?string $date;
    public ?string $period;

    public function __construct(string $title, ?string $type = null, ?string $date = null, ?string $period = null)
    {
        $this->title = $title;
        $this->type = $type;
        $this->date = $date;
        $this->period = $period;
    }

    public function render()
    {
        return view('components.dashboard.widget-heading');
    }
}
