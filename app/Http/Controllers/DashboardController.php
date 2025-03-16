<?php

namespace App\Http\Controllers;

use App\Models\EnergyBudget;
use App\Services\EnergyConversionService;
use Illuminate\Http\Request;
use App\Http\Controllers\EnergyVisualizationController;
use App\Services\EnergyPredictionService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private $conversionService;
    private $predictionService;
    private $energyVisController;
    public function __construct(EnergyConversionService $conversionService, EnergyPredictionService $predictionService)
    {
        $this->conversionService = $conversionService;
        $this->predictionService = $predictionService;
        $this->energyVisController = new EnergyVisualizationController($conversionService, $predictionService);
    }

    // $gridLayout = [
    //     ['widget' => 'date-selector', 'position' => 0],
    //     ['widget' => 'usage-prediction', 'position' => 1],
    //     ['widget' => 'energy-status-electricity', 'position' => 2],
    //     ['widget' => 'energy-status-gas', 'position' => 3],
    //     ['widget' => 'historical-comparison', 'position' => 4],
    //     ['widget' => 'energy-chart-electricity', 'position' => 5],
    //     ['widget' => 'energy-chart-gas', 'position' => 6],
    //     ['widget' => 'trend-analysis', 'position' => 7],
    //     ['widget' => 'energy-suggestions', 'position' => 8],
    // ];
    public function index(Request $request)
    {
        if(!Auth::check()){
            return view('/auth.login');
        }
        $energydashboard_data = $this->energyVisController->dashboard($request);
        $gridLayout = ['energy-status-electricity', 'energy-status-gas', 'energy-chart-electricity', 'energy-chart-gas'];
        $energydashboard_data['gridLayout'] = $gridLayout;

        $energydashboard_data['lastUpdated'] = now()->format('Y-m-d H:i:s');

        $refreshTimeInSeconds = 65;
        $refreshTime = now()->addSeconds($refreshTimeInSeconds);
        $energydashboard_data['refreshTime'] = $refreshTime->format('Y-m-d H:i:s');

        $timeDiff = $refreshTime->diffInSeconds(now());
        $energydashboard_data['refreshInSeconds'] = $timeDiff;

        //Chekced of er budget is gehaald uit de db. Deze redirect werkt niet binnen de dashboard() call naar eviscontroller. dus doe ik het tijdelijk zo.
        if (!isset($energydashboard_data['budget']) || $energydashboard_data['budget'] === null) {
            return redirect()->route('budget.form');
        }
        return view('dashboard', ['energydashboard_data' => $energydashboard_data]);
    }

    public function setWidget(){

    }
}
