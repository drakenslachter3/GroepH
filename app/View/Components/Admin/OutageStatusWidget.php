<?php

namespace App\View\Components\Admin;

use App\Models\InfluxOutage;
use App\Services\InfluxOutageService;
use Illuminate\View\Component;
use Illuminate\View\View;
use Carbon\Carbon;

class OutageStatusWidget extends Component
{
    public $activeOutages;
    public $recentOutages;
    public $upcomingOutages;

    public function __construct(InfluxOutageService $outageService)
    {
        $now = Carbon::now();
        
        // Actieve storingen (nu aan de gang)
        $this->activeOutages = InfluxOutage::active()
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->with('creator')
            ->get();

        // Recente storingen (laatste 7 dagen)
        $this->recentOutages = InfluxOutage::active()
            ->where('end_time', '>=', $now->copy()->subDays(7))
            ->where('end_time', '<', $now)
            ->with('creator')
            ->orderBy('end_time', 'desc')
            ->limit(5)
            ->get();

        // Aankomende storingen (geplande onderhoud)
        $this->upcomingOutages = InfluxOutage::active()
            ->where('start_time', '>', $now)
            ->where('start_time', '<=', $now->copy()->addDays(7))
            ->with('creator')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    public function render(): View
    {
        return view('components.admin.outage-status-widget');
    }
}