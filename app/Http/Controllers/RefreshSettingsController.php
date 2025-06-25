<?php

namespace App\Http\Controllers;

use App\Models\RefreshSettings;
use Illuminate\Http\Request;

class RefreshSettingsController extends Controller
{
    public function index()
    {
        $refreshInterval = RefreshSettings::get('dashboard_refresh_interval', 0);
        $allowPeakHoursRefresh = (bool) RefreshSettings::get('allow_peak_hours_refresh', 0);

        return view('admin.refresh-settings', compact('refreshInterval', 'allowPeakHoursRefresh'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'refresh_interval' => 'required|integer|min:0|max:3600',
            'allow_peak_hours_refresh' => 'boolean'
        ]);

        RefreshSettings::set('dashboard_refresh_interval', $request->refresh_interval);
        RefreshSettings::set('allow_peak_hours_refresh', $request->boolean('allow_peak_hours_refresh'));

        $messages = [];
        $messages[] = 'Refresh interval bijgewerkt naar ' .
            ($request->refresh_interval == 0 ? 'handmatig' : $request->refresh_interval . ' seconden');

        $messages[] = 'Spitsuur refresh ' .
            ($request->boolean('allow_peak_hours_refresh') ? 'ingeschakeld' : 'uitgeschakeld');

        return redirect()->back()->with('status', implode('. ', $messages));
    }
}
