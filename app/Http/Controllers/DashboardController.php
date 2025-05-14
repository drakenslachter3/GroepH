<?php

namespace App\Http\Controllers;

use App\Models\UserGridLayout;
use App\Services\EnergyConversionService;
use App\Services\EnergyPredictionService;
use App\Services\DashboardPredictionService; // Add this import
use Illuminate\Http\Request;
use App\Http\Controllers\EnergyVisualizationController;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private $energyVisController;
    private $predictionService; // Add this
    private $dashboardPredictionService; // Add this
    
    public function __construct(
        EnergyConversionService $conversionService, 
        EnergyPredictionService $predictionService,
        DashboardPredictionService $dashboardPredictionService // Add this
    )
    {
        $this->energyVisController = new EnergyVisualizationController($conversionService, $predictionService);
        $this->predictionService = $predictionService; // Add this
        $this->dashboardPredictionService = $dashboardPredictionService; // Add this
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Load user's smart meters with latest readings
        $user->load(['smartMeters', 'smartMeters.latestReading']);
        
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
        
        // Include the user with smart meters data
        $energydashboard_data['user'] = $user;

        // Get period and date from request
        $period = $request->query('period', 'month');
        $date = $request->query('date', date('Y-m-d'));
        
        // Add energy prediction data for both electricity and gas
        $predictionData = [];
        $budgetData = [];
        $predictionPercentage = [];
        $predictionConfidence = [];
        $yearlyConsumptionToDate = [];
        $dailyAverageConsumption = [];
        
        // Get electricity prediction data
        $electricityPredictionResult = $this->dashboardPredictionService->getDashboardPredictionData('electricity', $period, $date);
        $predictionData['electricity'] = $electricityPredictionResult['predictionData'];
        $budgetData['electricity'] = $electricityPredictionResult['budgetData'];
        $predictionConfidence['electricity'] = $electricityPredictionResult['confidence'];
        
        // Calculate electricity percentage
        $actualElectricity = array_sum(array_filter($predictionData['electricity']['actual'], function($value) { 
            return $value !== null; 
        }));
        
        $dateObj = Carbon::parse($date);
        $targetField = 'electricity_target_kwh';
        $monthlyTarget = $budgetData['electricity']['monthly_target'] ?? 0;
        
        if ($period === 'month') {
            $predictionPercentage['electricity'] = ($actualElectricity / $monthlyTarget) * 100;
        } else if ($period === 'day') {
            $dailyTarget = $monthlyTarget / $dateObj->daysInMonth;
            $predictionPercentage['electricity'] = ($actualElectricity / $dailyTarget) * 100;
        } else {
            $yearlyTarget = $budgetData['electricity']['target'] ?? 0;
            $currentDayOfYear = $dateObj->dayOfYear;
            $daysInYear = $dateObj->isLeapYear() ? 366 : 365;
            $proRatedBudget = $yearlyTarget * ($currentDayOfYear / $daysInYear);
            $predictionPercentage['electricity'] = ($actualElectricity / $proRatedBudget) * 100;
        }
        
        // Yearly consumption to date and daily average for electricity
        $yearlyConsumptionToDate['electricity'] = $this->getYearlyConsumptionToDate('electricity');
        $daysPassedThisYear = max(1, Carbon::now()->dayOfYear);
        $dailyAverageConsumption['electricity'] = $yearlyConsumptionToDate['electricity'] / $daysPassedThisYear;
        
        // Get gas prediction data
        $gasPredictionResult = $this->dashboardPredictionService->getDashboardPredictionData('gas', $period, $date);
        $predictionData['gas'] = $gasPredictionResult['predictionData'];
        $budgetData['gas'] = $gasPredictionResult['budgetData'];
        $predictionConfidence['gas'] = $gasPredictionResult['confidence'];
        
        // Calculate gas percentage
        $actualGas = array_sum(array_filter($predictionData['gas']['actual'], function($value) { 
            return $value !== null; 
        }));
        
        $targetField = 'gas_target_m3';
        $monthlyTarget = $budgetData['gas']['monthly_target'] ?? 0;
        
        if ($period === 'month') {
            $predictionPercentage['gas'] = ($actualGas / $monthlyTarget) * 100;
        } else if ($period === 'day') {
            $dailyTarget = $monthlyTarget / $dateObj->daysInMonth;
            $predictionPercentage['gas'] = ($actualGas / $dailyTarget) * 100;
        } else {
            $yearlyTarget = $budgetData['gas']['target'] ?? 0;
            $currentDayOfYear = $dateObj->dayOfYear;
            $daysInYear = $dateObj->isLeapYear() ? 366 : 365;
            $proRatedBudget = $yearlyTarget * ($currentDayOfYear / $daysInYear);
            $predictionPercentage['gas'] = ($actualGas / $proRatedBudget) * 100;
        }
        
        // Yearly consumption to date and daily average for gas
        $yearlyConsumptionToDate['gas'] = $this->getYearlyConsumptionToDate('gas');
        $dailyAverageConsumption['gas'] = $yearlyConsumptionToDate['gas'] / $daysPassedThisYear;
        
        // Add prediction data to the view
        $energydashboard_data['predictionData'] = $predictionData;
        $energydashboard_data['budgetData'] = $budgetData;
        $energydashboard_data['predictionPercentage'] = $predictionPercentage;
        $energydashboard_data['predictionConfidence'] = $predictionConfidence;
        $energydashboard_data['yearlyConsumptionToDate'] = $yearlyConsumptionToDate;
        $energydashboard_data['dailyAverageConsumption'] = $dailyAverageConsumption;

        return view('dashboard', $energydashboard_data);
    }

    /**
     * Get yearly consumption to date (simulated)
     * 
     * @param string $type Energy type (electricity or gas)
     * @return float Yearly consumption to date
     */
    private function getYearlyConsumptionToDate(string $type): float
    {
        // Simulated data - in a real implementation this would come from the database
        $currentDayOfYear = Carbon::now()->dayOfYear;
        $daysInYear = Carbon::now()->isLeapYear() ? 366 : 365;
        
        // Typical yearly consumption values
        $yearlyTotal = $type === 'electricity' ? 3200 : 1400;
        
        // Simulate realistic progress through the year
        $progressFactor = $currentDayOfYear / $daysInYear;
        
        // Seasonal adjustment
        $month = (int)date('n');
        $seasonalFactor = 1.0;
        
        if ($type === 'gas') {
            // Gas has stronger seasonal pattern
            $winterMonths = [1, 2, 3, 11, 12];
            $summerMonths = [6, 7, 8];
            
            if (in_array($month, $winterMonths)) {
                $seasonalFactor = 1.25;
            } elseif (in_array($month, $summerMonths)) {
                $seasonalFactor = 0.75;
            }
        } else {
            // Electricity has milder seasonal pattern
            $seasonalFactor = 1.0 + (cos(($month - 1) * 2 * M_PI / 12) * 0.1);
        }
        
        // Add slight random variation
        $randomFactor = 0.97 + (mt_rand(0, 6) / 100);
        
        // Calculate estimated consumption with some rounding
        $consumption = $yearlyTotal * $progressFactor * $seasonalFactor * $randomFactor;
        return round($consumption * 100) / 100;
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
            'energy-status-electricity',
            'energy-status-gas',
            'energy-chart-electricity',
            'energy-chart-gas',
            'usage-prediction', 
            'date-selector',
            'historical-comparison',
            'energy-suggestions',
            'energy-prediction-chart-electricity', // Add these new widget types
            'energy-prediction-chart-gas'          // Add these new widget types
        ];
    }
}