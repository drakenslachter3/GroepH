<?php
namespace App\Http\Controllers;

use App\Http\Controllers\EnergyVisualizationController;
use App\Models\SmartMeter;
use App\Models\UserGridLayout;
use App\Services\EnergyConversionService;
use App\Services\EnergyPredictionService;
use App\Services\InfluxDBService; // Add this import
use Carbon\Carbon;
use App\Services\EnergyNotificationService;
use App\Services\DashboardPredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private $energyVisController;
    private $notificationService;
    private $influxService; // Add this property

    public function __construct(
        EnergyConversionService $conversionService, 
        EnergyPredictionService $predictionService, 
        EnergyNotificationService $notificationService, 
        DashboardPredictionService $dashboardPredictionService,
        InfluxDBService $influxService // Inject InfluxDB service
    ) {
        $this->energyVisController = new EnergyVisualizationController($conversionService, $predictionService);
        $this->notificationService = $notificationService;
        $this->predictionService = $predictionService;
        $this->dashboardPredictionService = $dashboardPredictionService;
        $this->conversionService = $conversionService;
        $this->influxService = $influxService; // Set the property
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $defaultPeriod = 'day';
        $defaultDate = Carbon::today()->format('Y-m-d');
        $defaultMeterId = optional(SmartMeter::getAllSmartMetersForCurrentUser()->first())->meter_id
                        ?? '2019-ETI-EMON-V01-105C4E-16405E';

        $period = session('dashboard_period', $defaultPeriod);
        $date = session('dashboard_date', $defaultDate);
        $selectedMeterId = session('selected_meter_id', $defaultMeterId);

        if ($request->has('selectedMeterId')) {
            $selectedMeterId = $request->input('selectedMeterId');
            session(['selected_meter_id' => $selectedMeterId]);
        }

        if ($request->has('period') && $request->has('date')) {
            $period = $request->input('period');
            $date = $request->input('date');
            session([
                'dashboard_period' => $period,
                'dashboard_date' => $date,
            ]);
        }

        if ($period === 'year' && !preg_match('/^\d{4}$/', $date)) {
            $date = Carbon::today()->format('Y');
        } elseif ($period === 'month' && preg_match('/^\d{4}$/', $date)) { 
            $date .= '-01';
        } elseif ($period === 'day' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = $defaultDate;
        }

        session([
            'dashboard_period' => $period,
            'dashboard_date' => $date,
            'selected_meter_id' => $selectedMeterId,
        ]);

        // Load user's smart meters with latest readings
        $user->load(['smartMeters', 'smartMeters.latestReading']);

        // Get energy data from visualization controller
        $energydashboard_data = $this->energyVisController->dashboard($request);

        if (!isset($energydashboard_data['budget']) || $energydashboard_data['budget'] === null) {
            return redirect()->route('budget.form');
        }

        // Get user grid layout
        $userGridLayoutModel = UserGridLayout::firstOrCreate(
            ['user_id' => $user->id],
            ['layout' => $this->getDefaultLayout()]
        );
        $energydashboard_data['gridLayout'] = $userGridLayoutModel->layout;

        // Add metadata
        $energydashboard_data['lastRefresh'] = Carbon::now()->format('d-m-Y H:i:s');
        $energydashboard_data['user'] = $user;
        $energydashboard_data['period'] = $period;
        $energydashboard_data['date'] = $date;

        // Get InfluxDB meter data for the period
        $energydashboard_data['meterDataForPeriod'] = $this->getEnergyData($selectedMeterId, $period, $date);

        // Get live InfluxDB data for current usage
        $liveInfluxData = $this->getLiveInfluxData($selectedMeterId, $period, $date);

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
        
        // Calculate electricity percentage and get live data
        $actualElectricity = $liveInfluxData['total']['electricity_usage'] ?? 0;
        
        $dateObj = Carbon::parse($date);
        $targetField = 'electricity_target_kwh';
        $monthlyTarget = $budgetData['electricity']['monthly_target'] ?? 0;
        
        if ($period === 'month') {
            $predictionPercentage['electricity'] = $monthlyTarget > 0 ? ($actualElectricity / $monthlyTarget) * 100 : 0;
        } else if ($period === 'day') {
            $dailyTarget = $monthlyTarget / $dateObj->daysInMonth;
            $predictionPercentage['electricity'] = $dailyTarget > 0 ? ($actualElectricity / $dailyTarget) * 100 : 0;
        } else {
            $yearlyTarget = $budgetData['electricity']['target'] ?? 0;
            $currentDayOfYear = $dateObj->dayOfYear;
            $daysInYear = $dateObj->isLeapYear() ? 366 : 365;
            $proRatedBudget = $yearlyTarget * ($currentDayOfYear / $daysInYear);
            $predictionPercentage['electricity'] = $proRatedBudget > 0 ? ($actualElectricity / $proRatedBudget) * 100 : 0;
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
        
        // Calculate gas percentage and get live data
        $actualGas = $liveInfluxData['total']['gas_usage'] ?? 0;
        
        $targetField = 'gas_target_m3';
        $monthlyTarget = $budgetData['gas']['monthly_target'] ?? 0;
        
        if ($period === 'month') {
            $predictionPercentage['gas'] = $monthlyTarget > 0 ? ($actualGas / $monthlyTarget) * 100 : 0;
        } else if ($period === 'day') {
            $dailyTarget = $monthlyTarget / $dateObj->daysInMonth;
            $predictionPercentage['gas'] = $dailyTarget > 0 ? ($actualGas / $dailyTarget) * 100 : 0;
        } else {
            $yearlyTarget = $budgetData['gas']['target'] ?? 0;
            $currentDayOfYear = $dateObj->dayOfYear;
            $daysInYear = $dateObj->isLeapYear() ? 366 : 365;
            $proRatedBudget = $yearlyTarget * ($currentDayOfYear / $daysInYear);
            $predictionPercentage['gas'] = $proRatedBudget > 0 ? ($actualGas / $proRatedBudget) * 100 : 0;
        }
        
        // Yearly consumption to date and daily average for gas
        $yearlyConsumptionToDate['gas'] = $this->getYearlyConsumptionToDate('gas');
        $dailyAverageConsumption['gas'] = $yearlyConsumptionToDate['gas'] / $daysPassedThisYear;
        
        // Calculate targets for the selected period
        $electricityTarget = $this->calculateTargetForPeriod('electricity', $period, $date, $budgetData['electricity']);
        $gasTarget = $this->calculateTargetForPeriod('gas', $period, $date, $budgetData['gas']);
        
        // Calculate costs using the conversion service
        $electricityCost = $this->conversionService->kwhToEuro($actualElectricity);
        $gasCost = $this->conversionService->m3ToEuro($actualGas);
        
        // Determine status based on percentage
        $electricityStatus = $this->determineStatus($predictionPercentage['electricity']);
        $gasStatus = $this->determineStatus($predictionPercentage['gas']);
        
        // Create live data structure for energy status widgets
        $energydashboard_data['liveData'] = [
            'electricity' => [
                'usage' => $actualElectricity,
                'target' => $electricityTarget,
                'cost' => $electricityCost,
                'percentage' => $predictionPercentage['electricity'],
                'status' => $electricityStatus,
            ],
            'gas' => [
                'usage' => $actualGas,
                'target' => $gasTarget,
                'cost' => $gasCost,
                'percentage' => $predictionPercentage['gas'],
                'status' => $gasStatus,
            ]
        ];
        
        // Update totals with live data for backward compatibility
        $energydashboard_data['totals']['electricity_kwh'] = $actualElectricity;
        $energydashboard_data['totals']['electricity_target'] = $electricityTarget;
        $energydashboard_data['totals']['electricity_euro'] = $electricityCost;
        $energydashboard_data['totals']['electricity_percentage'] = $predictionPercentage['electricity'];
        $energydashboard_data['totals']['electricity_status'] = $electricityStatus;
        
        $energydashboard_data['totals']['gas_m3'] = $actualGas;
        $energydashboard_data['totals']['gas_target'] = $gasTarget;
        $energydashboard_data['totals']['gas_euro'] = $gasCost;
        $energydashboard_data['totals']['gas_percentage'] = $predictionPercentage['gas'];
        $energydashboard_data['totals']['gas_status'] = $gasStatus;
        
        // Add prediction data to the view
        $energydashboard_data['predictionData'] = $predictionData;
        $energydashboard_data['budgetData'] = $budgetData;
        $energydashboard_data['predictionPercentage'] = $predictionPercentage;
        $energydashboard_data['predictionConfidence'] = $predictionConfidence;
        $energydashboard_data['yearlyConsumptionToDate'] = $yearlyConsumptionToDate;
        $energydashboard_data['dailyAverageConsumption'] = $dailyAverageConsumption;

        // Generate notifications using live data
        if (Auth::check() && isset($energydashboard_data['totals'])) {
            $this->notificationService->generateNotificationsForUser(
                Auth::user(),
                $energydashboard_data['totals']['electricity_prediction'] ?? [],
                $energydashboard_data['totals']['gas_prediction'] ?? [],
                $period
            );
        }

        return view('dashboard', $energydashboard_data);
    }

    /**
     * Get live InfluxDB data for the selected meter and period
     */
    private function getLiveInfluxData(string $meterId, string $period, string $date): array
    {
        try {
            // Get real-time meter data from InfluxDB
            return $this->influxService->getEnergyDashboardData($meterId, $period, $date);
        } catch (\Exception $e) {
            Log::error('Error fetching live InfluxDB data: ' . $e->getMessage());
            
            // Return fallback data structure if InfluxDB is unavailable
            return [
                'current_data' => [],
                'historical_data' => [],
                'total' => [
                    'electricity_usage' => 0,
                    'gas_usage' => 0,
                    'electricity_generation' => 0,
                ]
            ];
        }
    }

    /**
     * Calculate target for a specific period
     */
    private function calculateTargetForPeriod(string $type, string $period, string $date, array $budgetData): float
    {
        $yearlyTarget = $budgetData['target'] ?? 0;
        $monthlyTarget = $budgetData['monthly_target'] ?? ($yearlyTarget / 12);
        $dateObj = Carbon::parse($date);
        
        switch ($period) {
            case 'day':
                return $monthlyTarget / $dateObj->daysInMonth;
            case 'month':
                return $monthlyTarget;
            case 'year':
                // For year view, calculate pro-rated target based on current day of year
                $currentDayOfYear = $dateObj->dayOfYear;
                $daysInYear = $dateObj->isLeapYear() ? 366 : 365;
                return $yearlyTarget * ($currentDayOfYear / $daysInYear);
            default:
                return $monthlyTarget;
        }
    }

    /**
     * Determine status based on percentage
     */
    private function determineStatus(float $percentage): string
    {
        if ($percentage > 95) {
            return 'kritiek';
        } elseif ($percentage > 80) {
            return 'waarschuwing';
        }
        return 'goed';
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
            'period'       => 'required|in:day,month,year',
            'date'         => 'required',
            'housing_type' => 'required|string',
        ]);

        $period      = $request->input('period');
        $housingType = $request->input('housing_type');
        $inputDate = $request->input('date');

        // Format the date based on the period type
        $formattedDate = $this->formatDateByPeriod($period, $inputDate);

        // Redirect back to dashboard with the new parameters
        return redirect()->route('dashboard', [
            'period'       => $period,
            'date'         => $formattedDate,
            'housing_type' => $housingType,
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
                    return $inputDate;
                }
                return $inputDate;

            case 'year':
                // For year period, ensure we have YYYY-MM-DD with first day of year
                if (strlen($inputDate) === 4) { // YYYY format
                    return $inputDate;
                }
                return $inputDate;

            default:
                // Default to current date if something goes wrong
                return Carbon::now()->format('Y-m-d');
        }
    }

    public function setWidget(Request $request)
    {
        $user       = Auth::user();
        $position   = (int) $request->input('grid_position');
        $widgetType = $request->input('widget_type');

        $request->validate([
            'grid_position' => 'required|numeric',
            'widget_type'   => 'required|string',
        ]);

        $userGridLayout = UserGridLayout::firstOrCreate(
            ['user_id' => $user->id],
            ['layout' => $this->getDefaultLayout()]
        );

        $gridLayout  = $userGridLayout->layout;
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
            'trend-analysis', // Behouden van dev branch
            'energy-suggestions',
            'energy-prediction-chart-electricity', // Behouden van SCRUM-53
            'energy-prediction-chart-gas'          // Behouden van SCRUM-53
        ];
    }

    public function saveSelectedMeter(Request $request)
    {
        $meterDatabaseId = $request->meter;
        $layout  = UserGridLayout::where('user_id', auth()->id())->first();

        if ($layout) {
            $layout->selected_smartmeter = $meterDatabaseId;
            $layout->save();
        } else {
            throw new \Exception('[SaveSelectedMeter, DashboardController]: meter kan niet opgeslagen worden, omdat user_grid_layout nog niet bestaat voor deze gebruiker!');
        }

        $smartMeterId = SmartMeter::getMeterIdByDatabaseId($meterDatabaseId);

        return redirect()->route('dashboard', ['selectedMeterId' => $smartMeterId])
                         ->with('status', 'Meterkeuze doorgevoerd - het dashboard is nu up-to-date!');
    }

    private function getEnergyData(string $meterId, string $period, string $date)
    {
        // // Probeer eerst uit de MySQL database op te halen
        $latestData = \App\Models\InfluxData::where('tags->meter_id', $meterId)
            ->where('tags->period', $period)
            ->where('tags->date', $date)
            ->orderBy('time', 'desc')
            ->first();

        if ($latestData) {
            return $latestData->fields;
        }

        // Als er geen gegevens zijn, haal ze dan op en sla ze op
        $influxService = app(\App\Services\InfluxDBService::class);
        $result        = $influxService->storeEnergyDashboardData($meterId, $period, $date);

        return $result['data'];
    }
}