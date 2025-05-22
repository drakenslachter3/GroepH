<?php

namespace App\Http\Controllers;

use App\Models\PredictionSetting;
use Illuminate\Http\Request;

class PredictionSettingsController extends Controller
{
    /**
     * Show the settings page
     */
    public function index()
    {
        $globalSettings = PredictionSetting::where('type', 'global')->get();
        $electricitySettings = PredictionSetting::where('type', 'electricity')->get();
        $gasSettings = PredictionSetting::where('type', 'gas')->get();
        
        return view('admin.prediction-settings', [
            'globalSettings' => $globalSettings,
            'electricitySettings' => $electricitySettings,
            'gasSettings' => $gasSettings
        ]);
    }
    
    /**
     * Update prediction settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.type' => 'required|string|in:global,electricity,gas',
            'settings.*.period' => 'required|string|in:all,day,month,year',
            'settings.*.best_case_margin' => 'required|numeric|min:0|max:50',
            'settings.*.worst_case_margin' => 'required|numeric|min:0|max:50',
        ]);
        
        foreach ($request->settings as $setting) {
            PredictionSetting::updateOrCreate(
                [
                    'type' => $setting['type'],
                    'period' => $setting['period']
                ],
                [
                    'best_case_margin' => $setting['best_case_margin'],
                    'worst_case_margin' => $setting['worst_case_margin'],
                    'active' => true
                ]
            );
        }
        
        return redirect()->route('admin.prediction-settings.index')
            ->with('status', 'Voorspellingsmarges zijn succesvol bijgewerkt.');
    }
}