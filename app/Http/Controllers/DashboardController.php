<?php

namespace App\Http\Controllers;

use App\Models\UserGridLayout;
use App\Services\EnergyConversionService;
use Illuminate\Http\Request;
use App\Http\Controllers\EnergyVisualizationController;
use App\Services\EnergyPredictionService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
        
        // Add last refresh time information
        $energydashboard_data['lastRefresh'] = Carbon::now()->format('d-m-Y H:i:s');

        return view('dashboard', $energydashboard_data);
    }

    // New method to handle date and period settings
    public function setTime(Request $request)
    {
        $request->validate([
            'period' => 'required|in:day,month,year',
            'date' => 'required',
            'housing_type' => 'required|string',
        ]);

        $period = $request->input('period');
        $housingType = $request->input('housing_type');
        $inputDate = $request->input('date');
        
        // Format the date based on the period type
        $formattedDate = $this->formatDateByPeriod($period, $inputDate);
        
        // Redirect back to dashboard with the new parameters
        return redirect()->route('dashboard', [
            'period' => $period,
            'date' => $formattedDate,
            'housing_type' => $housingType
        ]);
    }
    
    // Helper method to format dates based on period
    private function formatDateByPeriod($period, $inputDate)
    {
        switch ($period) {
            case 'day':
                // For day period, the date should already be in YYYY-MM-DD format
                return $inputDate;
                
            case 'month':
                // For month period, ensure we have YYYY-MM-DD with first day of month
                if (strlen($inputDate) === 7) { // YYYY-MM format
                    return $inputDate . '-01';
                }
                return $inputDate;
                
            case 'year':
                // For year period, ensure we have YYYY-MM-DD with first day of year
                if (strlen($inputDate) === 4) { // YYYY format
                    return $inputDate . '-01-01';
                }
                return $inputDate;
                
            default:
                // Default to current date if something goes wrong
                return Carbon::now()->format('Y-m-d');
        }
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

    private function getDefaultLayout()
    {
        return [
            'switch-meter',
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

    public function saveSelectedMeter(Request $request){
        $meterId = $request->meter;
        $layout = UserGridLayout::where('user_id', auth()->id())->first();

        if ($layout) {
            $layout->selected_smartmeter = $meterId;
            $layout->save();
        } else {
            throw new \Exception('[SaveSelectedMeter, DashboardController]: meter kan niet opgeslagen worden, omdat user_grid_layout nog niet bestaat voor deze gebruiker!');
        }

        return redirect()->route('dashboard')->with('status', 'Meterkeuze doorgevoerd - het dashboard is nu up-to-date!');
    }
}