<?php

namespace App\Http\Controllers;

use App\Models\UserGridLayout;
use App\Services\EnergyConversionService;
use Illuminate\Http\Request;
use App\Http\Controllers\EnergyVisualizationController;
use App\Services\EnergyPredictionService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private $energyVisController;
    public function __construct(EnergyConversionService $conversionService, EnergyPredictionService $predictionService)
    {
        $this->energyVisController = new EnergyVisualizationController($conversionService, $predictionService);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $energydashboard_data = $this->energyVisController->dashboard($request);

        $userGridLayoutModel = UserGridLayout::firstOrCreate(
            ['user_id' => $user->id],
            ['layout' => $this->getDefaultLayout()]
        );

        $energydashboard_data['gridLayout'] = $userGridLayoutModel->layout;

        if (!isset($energydashboard_data['budget']) || $energydashboard_data['budget'] === null) {
            return redirect()->route('budget.form');
        }

        return view('dashboard', $energydashboard_data);
    }

    private function getDefaultLayout()
    {
        return [
            'energy-status-electricity',
            'energy-status-gas',
            'energy-chart-electricity',
            'energy-chart-gas',
            'usage-prediction', 
            'date-selector',
            'historical-comparison',
            'trend-analysis',
            'energy-suggestions'
        ];
    }

    public function setWidget(Request $request)
    {
        $user = Auth::user();
        $position = (int) $request->input('grid_position');
        $widgetType = $request->input('widget_type');

        $request->validate([
            'grid_position' => 'required|numeric',
            'widget_type' => 'required|string',
        ]);

        $userGridLayout = UserGridLayout::firstOrCreate(
            ['user_id' => $user->id],
            ['layout' => $this->getDefaultLayout()]
        );

        $gridLayout = $userGridLayout->layout;
        $widgetTypes = [$widgetType];

        foreach ($widgetTypes as $widget) {
            $currentIndex = array_search($widget, $gridLayout);
            if ($currentIndex !== false) {
                array_splice($gridLayout, $currentIndex, 1);

                if ($currentIndex < $position) {
                    $position--;
                }
            }
            array_splice($gridLayout, $position, 0, [$widget]);

            $position++;
        }

        $userGridLayout->layout = $gridLayout;
        $userGridLayout->save();

        return redirect()->route('dashboard')->with('status', 'Widget toegevoegd!');
    }
    public function resetLayout(Request $request)
    {
        $user = Auth::user();
        UserGridLayout::updateOrCreate(
            ['user_id' => $user->id],
            ['layout' => $this->getDefaultLayout()]
        );

        return redirect()->route('dashboard')->with('status', 'Dashboard layout is gereset!');
    }
}
