<?php

namespace App\Http\Controllers;

use App\Models\RefreshSettings;
use Illuminate\Http\Request;

class RefreshSettingsController extends Controller
{
    public function index()
    {
        $refreshInterval = RefreshSettings::get('dashboard_refresh_interval', 0);
        return view('admin.refresh-settings', compact('refreshInterval'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'refresh_interval' => 'required|integer|min:0|max:3600'
        ]);

        RefreshSettings::set('dashboard_refresh_interval', $request->refresh_interval);

        return redirect()->back()->with('status', 'Refresh interval bijgewerkt naar ' .
            ($request->refresh_interval == 0 ? 'handmatig' : $request->refresh_interval . ' seconden'));
    }
}
