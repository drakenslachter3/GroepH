<?php
namespace App\View\Components\Dashboard;

use Illuminate\View\Component;

class WidgetHeading extends Component
{
    /** Title of the widget */
    public string $title;

    /** Optional energy type of the widget (e.g. electricity, gas) */
    public ?string $type;

    /** Optional date related to the widget data */
    public ?string $date;

    /** Optional period for the widget data (e.g., day, month, year) */
    public ?string $period;

    /**
     * Create a new component instance.
     */
    public function __construct(string $title, ?string $type = null, ?string $date = null, ?string $period = null)
    {
        $this->title = $title;
        $this->type = $type;
        $this->date = $date;
        $this->period = $period;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.dashboard.widget-heading');
    }
}
