<?php
namespace App\Http\Controllers;

use App\Http\Controllers\EnergyVisualizationController;
use App\Http\Middleware\CheckBudgetSetup;
use App\Models\EnergyStatusData;
use App\Models\SmartMeter;
use App\Models\UserGridLayout;
use App\Services\DashboardPredictionService;
use App\Services\EnergyConversionService;
use App\Services\EnergyNotificationService;
use App\Services\EnergyPredictionService;
use App\Services\InfluxDBService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private $energyVisController;
    private $notificationService;
    private $influxService;

    public function __construct(
        EnergyConversionService $conversionService,
        EnergyPredictionService $predictionService,
        EnergyNotificationService $notificationService,
        DashboardPredictionService $dashboardPredictionService,
        InfluxDBService $influxService
    ) {
        $this->energyVisController        = new EnergyVisualizationController($conversionService, $predictionService);
        $this->notificationService        = $notificationService;
        $this->predictionService          = $predictionService;
        $this->dashboardPredictionService = $dashboardPredictionService;
        $this->conversionService          = $conversionService;
        $this->influxService              = $influxService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $defaultPeriod  = 'day';
        $defaultDate    = Carbon::today()->format('Y-m-d');
        $defaultMeterId = optional(SmartMeter::getAllSmartMetersForCurrentUser()->first())->meter_id ?? '2019-ETI-EMON-V01-105C4E-16405E';

        $period          = session('dashboard_period', $defaultPeriod);
        $date            = session('dashboard_date', $defaultDate);
        $selectedMeterId = session('selected_meter_id', $defaultMeterId);

        if ($request->has('selectedMeterId')) {
            $selectedMeterId = $request->input('selectedMeterId');
            session(['selected_meter_id' => $selectedMeterId]);
        }

        if ($request->has('period') && $request->has('date')) {
            $period = $request->input('period');
            $date   = $request->input('date');
            session([
                'dashboard_period' => $period,
                'dashboard_date'   => $date,
            ]);
        }

        if ($period === 'year' && ! preg_match('/^\d{4}$/', $date)) {
            $date = Carbon::today()->format('Y');
        } elseif ($period === 'month' && preg_match('/^\d{4}$/', $date)) {
            $date .= '-01';
        } elseif ($period === 'day' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = $defaultDate;
        }

        session([
            'dashboard_period'  => $period,
            'dashboard_date'    => $date,
            'selected_meter_id' => $selectedMeterId,
        ]);

        $user->load(['smartMeters', 'smartMeters.latestReading']);

        $selectedMeter = SmartMeter::where('meter_id', $selectedMeterId)->first();
        $meterBudget   = null;

        if ($selectedMeter) {
            $meterBudget = \App\Models\EnergyBudget::where('user_id', $user->id)
                ->where('smart_meter_id', $selectedMeter->id)
                ->where('year', Carbon::parse($date)->year)
                ->with(['monthlyBudgets' => function ($query) {
                    $query->orderBy('month');
                }])
                ->first();
        }

        if (! $meterBudget) {
            return redirect()->route('budget.form')
                ->with('error', 'Geen budget gevonden voor de geselecteerde meter. Stel eerst een budget in.');
        }

        $energydashboard_data = $this->energyVisController->dashboard($request);

        if (! isset($energydashboard_data['budget']) || $energydashboard_data['budget'] === null) {
            return redirect()->route('budget.form');
        }

        $userGridLayoutModel = UserGridLayout::firstOrCreate(
            ['user_id' => $user->id],
            ['layout' => $this->getDefaultLayout()]
        );
        $energydashboard_data['gridLayout'] = $userGridLayoutModel->layout;

        $energydashboard_data['lastRefresh'] = Carbon::now()->format('d-m-Y H:i:s');
        $energydashboard_data['user']        = $user;
        $energydashboard_data['period']      = $period;
        $energydashboard_data['date']        = $date;

        $energydashboard_data['meterDataForPeriod'] = $this->getEnergyData($selectedMeterId, $period, $date);

        $liveInfluxData = $this->getLiveInfluxData($selectedMeterId, $period, $date);

        $meterDataForPeriod = $this->getEnergyData($selectedMeterId, $period, $date);

        \Log::debug("Meter data structure: " . json_encode(array_keys($meterDataForPeriod)));

        $hasValidInfluxData =
        isset($meterDataForPeriod['current_data']) &&
            (
            (isset($meterDataForPeriod['current_data']['energy_consumed']) && ! empty($meterDataForPeriod['current_data']['energy_consumed'])) ||
            (isset($meterDataForPeriod['current_data']['gas_delivered']) && ! empty($meterDataForPeriod['current_data']['gas_delivered']))
        );

        if ($hasValidInfluxData) {
            \Log::debug("Using real InfluxDB data for predictions");
        } else {
            \Log::debug("Using simulated data for predictions - no valid InfluxDB data available");
        }

        if ($hasValidInfluxData) {
            $electricityPredictionResult = $this->dashboardPredictionService->getDashboardPredictionDataWithRealData(
                'electricity',
                $period,
                $date,
                $meterDataForPeriod
            );
            $predictionData['electricity']       = $electricityPredictionResult['predictionData'];
            $budgetData['electricity']           = $electricityPredictionResult['budgetData'];
            $predictionConfidence['electricity'] = $electricityPredictionResult['confidence'];

            $gasPredictionResult = $this->dashboardPredictionService->getDashboardPredictionDataWithRealData(
                'gas',
                $period,
                $date,
                $meterDataForPeriod
            );
            $predictionData['gas']       = $gasPredictionResult['predictionData'];
            $budgetData['gas']           = $gasPredictionResult['budgetData'];
            $predictionConfidence['gas'] = $gasPredictionResult['confidence'];
        } else {
            $electricityPredictionResult = $this->dashboardPredictionService->getDashboardPredictionData(
                'electricity',
                $period,
                $date
            );
            $predictionData['electricity']       = $electricityPredictionResult['predictionData'];
            $budgetData['electricity']           = $electricityPredictionResult['budgetData'];
            $predictionConfidence['electricity'] = $electricityPredictionResult['confidence'];

            $gasPredictionResult = $this->dashboardPredictionService->getDashboardPredictionData(
                'gas',
                $period,
                $date
            );
            $predictionData['gas']       = $gasPredictionResult['predictionData'];
            $budgetData['gas']           = $gasPredictionResult['budgetData'];
            $predictionConfidence['gas'] = $gasPredictionResult['confidence'];
        }

        $currentMonth             = Carbon::parse($date)->month;
        $monthlyElectricityBudget = $meterBudget->monthlyBudgets->firstWhere('month', $currentMonth);
        $monthlyGasBudget         = $meterBudget->monthlyBudgets->firstWhere('month', $currentMonth);

        $monthlyElectricityTarget = $monthlyElectricityBudget ?
        $monthlyElectricityBudget->electricity_target_kwh :
        ($meterBudget->electricity_target_kwh / 12);

        $monthlyGasTarget = $monthlyGasBudget ?
        $monthlyGasBudget->gas_target_m3 :
        ($meterBudget->gas_target_m3 / 12);

        $budgetData['electricity'] = [
            'target'         => $meterBudget->electricity_target_kwh,
            'monthly_target' => $monthlyElectricityTarget,
            'per_unit'       => null,
            'line'           => $this->getMeterMonthlyBudgetLine($meterBudget, 'electricity'),
        ];

        $budgetData['gas'] = [
            'target'         => $meterBudget->gas_target_m3,
            'monthly_target' => $monthlyGasTarget,
            'per_unit'       => null,
            'line'           => $this->getMeterMonthlyBudgetLine($meterBudget, 'gas'),
        ];

        $actualElectricity = $liveInfluxData['total']['electricity_usage'] ?? 0;

        $dateObj     = Carbon::parse($date);
        $targetField = 'electricity_target_kwh';

        if ($period === 'month') {
            $predictionPercentage['electricity'] = $monthlyElectricityTarget > 0 ? ($actualElectricity / $monthlyElectricityTarget) * 100 : 0;
        } else if ($period === 'day') {
            $dailyTarget                         = $monthlyElectricityTarget / $dateObj->daysInMonth;
            $predictionPercentage['electricity'] = $dailyTarget > 0 ? ($actualElectricity / $dailyTarget) * 100 : 0;
        } else {
            $yearlyTarget                        = $meterBudget->electricity_target_kwh;
            $currentDayOfYear                    = $dateObj->dayOfYear;
            $daysInYear                          = $dateObj->isLeapYear() ? 366 : 365;
            $proRatedBudget                      = $yearlyTarget * ($currentDayOfYear / $daysInYear);
            $predictionPercentage['electricity'] = $proRatedBudget > 0 ? ($actualElectricity / $proRatedBudget) * 100 : 0;
        }

        $yearlyConsumptionToDate['electricity'] = $this->getYearlyConsumptionToDate('electricity');
        $daysPassedThisYear                     = max(1, Carbon::now()->dayOfYear);
        $dailyAverageConsumption['electricity'] = $yearlyConsumptionToDate['electricity'] / $daysPassedThisYear;

        $actualGas = $liveInfluxData['total']['gas_usage'] ?? 0;

        if ($period === 'month') {
            $predictionPercentage['gas'] = $monthlyGasTarget > 0 ? ($actualGas / $monthlyGasTarget) * 100 : 0;
        } else if ($period === 'day') {
            $dailyTarget                 = $monthlyGasTarget / $dateObj->daysInMonth;
            $predictionPercentage['gas'] = $dailyTarget > 0 ? ($actualGas / $dailyTarget) * 100 : 0;
        } else {
            $yearlyTarget                = $meterBudget->gas_target_m3;
            $currentDayOfYear            = $dateObj->dayOfYear;
            $daysInYear                  = $dateObj->isLeapYear() ? 366 : 365;
            $proRatedBudget              = $yearlyTarget * ($currentDayOfYear / $daysInYear);
            $predictionPercentage['gas'] = $proRatedBudget > 0 ? ($actualGas / $proRatedBudget) * 100 : 0;
        }

        $yearlyConsumptionToDate['gas'] = $this->getYearlyConsumptionToDate('gas');
        $dailyAverageConsumption['gas'] = $yearlyConsumptionToDate['gas'] / $daysPassedThisYear;

        $electricityTarget = $this->calculateMeterTargetForPeriod('electricity', $period, $date, $meterBudget);
        $gasTarget         = $this->calculateMeterTargetForPeriod('gas', $period, $date, $meterBudget);

        $electricityCost = $this->conversionService->kwhToEuro($actualElectricity);
        $gasCost         = $this->conversionService->m3ToEuro($actualGas);

        $electricityStatus = $this->determineStatus($predictionPercentage['electricity']);
        $gasStatus         = $this->determineStatus($predictionPercentage['gas']);

        $budgetStatus                         = CheckBudgetSetup::getBudgetSetupStatus();
        $energydashboard_data['budgetStatus'] = $budgetStatus;

        $energydashboard_data['liveData'] = [
            'electricity' => [
                'usage'         => $actualElectricity,
                'target'        => $electricityTarget,
                'cost'          => $electricityCost,
                'percentage'    => $electricityTarget > 0 ? ($actualElectricity / $electricityTarget) * 100 : 0,
                'status'        => $electricityStatus,
                'budget_id'     => $meterBudget->id,
                'meter_id'      => $selectedMeter->id,
                'previous_year' => [
                    'usage'             => $liveInfluxData['historical_data']['energy_consumed'] ?? [],
                    'reduction_percent' => $this->calculateReductionPercentage(
                        $actualElectricity,
                        isset($liveInfluxData['historical_data']['energy_consumed']) ?
                        array_sum(array_filter($liveInfluxData['historical_data']['energy_consumed'], 'is_numeric')) : 0
                    ),
                ],
            ],
            'gas'         => [
                'usage'         => $actualGas,
                'target'        => $gasTarget,
                'cost'          => $gasCost,
                'percentage'    => $gasTarget > 0 ? ($actualGas / $gasTarget) * 100 : 0,
                'status'        => $gasStatus,
                'budget_id'     => $meterBudget->id,
                'meter_id'      => $selectedMeter->id,
                'previous_year' => [
                    'usage'             => $liveInfluxData['historical_data']['gas_delivered'] ?? [],
                    'reduction_percent' => $this->calculateReductionPercentage(
                        $actualGas,
                        isset($liveInfluxData['historical_data']['gas_delivered']) ?
                        array_sum(array_filter($liveInfluxData['historical_data']['gas_delivered'], 'is_numeric')) : 0
                    ),
                ],
            ],
        ];

        $energydashboard_data['totals']['electricity_kwh']        = $actualElectricity;
        $energydashboard_data['totals']['electricity_target']     = $electricityTarget;
        $energydashboard_data['totals']['electricity_euro']       = $electricityCost;
        $energydashboard_data['totals']['electricity_percentage'] = $predictionPercentage['electricity'];
        $energydashboard_data['totals']['electricity_status']     = $electricityStatus;

        $energydashboard_data['totals']['gas_m3']         = $actualGas;
        $energydashboard_data['totals']['gas_target']     = $gasTarget;
        $energydashboard_data['totals']['gas_euro']       = $gasCost;
        $energydashboard_data['totals']['gas_percentage'] = $predictionPercentage['gas'];
        $energydashboard_data['totals']['gas_status']     = $gasStatus;

        $energydashboard_data['predictionData']          = $predictionData;
        $energydashboard_data['budgetData']              = $budgetData;
        $energydashboard_data['predictionPercentage']    = $predictionPercentage;
        $energydashboard_data['predictionConfidence']    = $predictionConfidence;
        $energydashboard_data['yearlyConsumptionToDate'] = $yearlyConsumptionToDate;
        $energydashboard_data['dailyAverageConsumption'] = $dailyAverageConsumption;

        $energydashboard_data['meterDataForPeriod'] = $meterDataForPeriod;

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
     * Calculate reduction percentage between current and previous year
     */
    private function calculateReductionPercentage(float $currentValue, float $previousValue): float
    {
        if ($previousValue <= 0) {
            return 0;
        }

        // Calculate how much less (positive) or more (negative) we're using compared to last year
        return round((($previousValue - $currentValue) / $previousValue) * 100, 1);
    }

    // In app/Http/Controllers/DashboardController.php

/**
 * Get live energy status data with MySQL caching
 */
    private function getLiveInfluxData(string $meterId, string $period, string $date): array
    {
        try {
            // Check if we have recent data in MySQL (less than 15 minutes old)
            $cachedData = EnergyStatusData::where('meter_id', $meterId)
                ->where('period', $period)
                ->where('date', $date)
                ->where('last_updated', '>', now()->subMinutes(15))
                ->first();

            if ($cachedData) {
                // Return cached data from MySQL
                return [
                    'current_data'    => $this->getEnergyData($meterId, $period, $date)['current_data'] ?? [],
                    'historical_data' => $this->getEnergyData($meterId, $period, $date)['historical_data'] ?? [],
                    'total'           => [
                        'electricity_usage'      => $cachedData->electricity_usage,
                        'gas_usage'              => $cachedData->gas_usage,
                        'electricity_generation' => 0, // Default value if not stored
                    ],
                ];
            }

            // If no cached data or it's stale, fetch from InfluxDB
            $influxData = $this->influxService->getEnergyDashboardData($meterId, $period, $date);

            // Calculate totals for the given period
            $electricityUsage = 0;
            $gasUsage         = 0;

            // Sum up the values based on period
            if (isset($influxData['current_data'])) {
                // For electricity
                if (isset($influxData['current_data']['energy_consumed'])) {
                    $electricityUsage = array_sum(array_filter($influxData['current_data']['energy_consumed'], function ($value) {
                        return is_numeric($value);
                    }));
                }

                // For gas
                if (isset($influxData['current_data']['gas_delivered'])) {
                    $gasUsage = array_sum(array_filter($influxData['current_data']['gas_delivered'], function ($value) {
                        return is_numeric($value);
                    }));
                }
            }

            // If we don't have data from the current period, use the total data
            if ($electricityUsage == 0 && isset($influxData['total']['month']['electricity_usage'])) {
                $electricityUsage = $influxData['total']['month']['electricity_usage'];
            }

            if ($gasUsage == 0 && isset($influxData['total']['month']['gas_usage'])) {
                $gasUsage = $influxData['total']['month']['gas_usage'];
            }

            // Calculate targets for the selected period
            $electricityTarget = $this->calculateTargetForPeriod('electricity', $period, $date, $budgetData['electricity'] ?? []);
            $gasTarget         = $this->calculateTargetForPeriod('gas', $period, $date, $budgetData['gas'] ?? []);

            // Calculate costs using the conversion service
            $electricityCost = $this->conversionService->kwhToEuro($electricityUsage);
            $gasCost         = $this->conversionService->m3ToEuro($gasUsage);

            // Calculate percentages
            $electricityPercentage = $electricityTarget > 0 ? ($electricityUsage / $electricityTarget) * 100 : 0;
            $gasPercentage         = $gasTarget > 0 ? ($gasUsage / $gasTarget) * 100 : 0;

            // Determine status based on percentage
            $electricityStatus = $this->determineStatus($electricityPercentage);
            $gasStatus         = $this->determineStatus($gasPercentage);

            // Calculate reduction percentages
            $electricityReductionPercent = $this->calculateReductionPercentage(
                $electricityUsage,
                isset($influxData['historical_data']['energy_consumed']) ?
                array_sum(array_filter($influxData['historical_data']['energy_consumed'], 'is_numeric')) : 0
            );

            $gasReductionPercent = $this->calculateReductionPercentage(
                $gasUsage,
                isset($influxData['historical_data']['gas_delivered']) ?
                array_sum(array_filter($influxData['historical_data']['gas_delivered'], 'is_numeric')) : 0
            );

            // Save to MySQL for future use
            EnergyStatusData::updateOrCreate(
                [
                    'meter_id' => $meterId,
                    'period'   => $period,
                    'date'     => $date,
                ],
                [
                    'electricity_usage'         => $electricityUsage,
                    'electricity_target'        => $electricityTarget,
                    'electricity_cost'          => $electricityCost,
                    'electricity_percentage'    => $electricityPercentage,
                    'electricity_status'        => $electricityStatus,
                    'electricity_previous_year' => [
                        'usage'             => $influxData['historical_data']['energy_consumed'] ?? [],
                        'reduction_percent' => $electricityReductionPercent,
                    ],
                    'gas_usage'                 => $gasUsage,
                    'gas_target'                => $gasTarget,
                    'gas_cost'                  => $gasCost,
                    'gas_percentage'            => $gasPercentage,
                    'gas_status'                => $gasStatus,
                    'gas_previous_year'         => [
                        'usage'             => $influxData['historical_data']['gas_delivered'] ?? [],
                        'reduction_percent' => $gasReductionPercent,
                    ],
                    'last_updated'              => now(),
                ]
            );

            // Return structured data for both charts and status components
            return [
                'current_data'    => $influxData['current_data'],
                'historical_data' => $influxData['historical_data'],
                'total'           => [
                    'electricity_usage'      => $electricityUsage,
                    'gas_usage'              => $gasUsage,
                    'electricity_generation' => $influxData['total']['month']['electricity_generation'] ?? 0,
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Error fetching live InfluxDB data: ' . $e->getMessage());

            // Return fallback data structure if InfluxDB is unavailable
            return [
                'current_data'    => [],
                'historical_data' => [],
                'total'           => [
                    'electricity_usage'      => 0,
                    'gas_usage'              => 0,
                    'electricity_generation' => 0,
                ],
            ];
        }
    }

    /**
     * Calculate target for a specific period
     */
    private function calculateTargetForPeriod(string $type, string $period, string $date, array $budgetData): float
    {
        $yearlyTarget  = $budgetData['target'] ?? 0;
        $monthlyTarget = $budgetData['monthly_target'] ?? ($yearlyTarget / 12);
        $dateObj       = Carbon::parse($date);

        switch ($period) {
            case 'day':
                // Daily target - adjusted for days in month
                $daysInMonth = $dateObj->daysInMonth;
                return $monthlyTarget / $daysInMonth;

            case 'month':
                // Monthly target - already calculated
                return $monthlyTarget;

            case 'year':
                // For yearly view:
                // If we're viewing current year to date, prorate the target
                $currentYear = Carbon::now()->year;
                if ($dateObj->year == $currentYear) {
                    $dayOfYear  = Carbon::now()->dayOfYear;
                    $daysInYear = Carbon::now()->isLeapYear() ? 366 : 365;
                    return $yearlyTarget * ($dayOfYear / $daysInYear);
                }
                // Otherwise, show full yearly target
                return $yearlyTarget;

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
 * Get monthly budget line for a meter
 */
    private function getMeterMonthlyBudgetLine(\App\Models\EnergyBudget $meterBudget, string $type): array
    {
        $targetField = $type === 'electricity' ? 'electricity_target_kwh' : 'gas_target_m3';
        $monthlyLine = [];

        // Get actual monthly budgets
        for ($month = 1; $month <= 12; $month++) {
            $monthlyBudget = $meterBudget->monthlyBudgets->firstWhere('month', $month);
            if ($monthlyBudget) {
                $monthlyLine[] = $monthlyBudget->$targetField;
            } else {
                // Fallback to yearly budget divided by 12
                $monthlyLine[] = $meterBudget->$targetField / 12;
            }
        }

        return $monthlyLine;
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
        $daysInYear       = Carbon::now()->isLeapYear() ? 366 : 365;

        // Typical yearly consumption values
        $yearlyTotal = $type === 'electricity' ? 3200 : 1400;

        // Simulate realistic progress through the year
        $progressFactor = $currentDayOfYear / $daysInYear;

        // Seasonal adjustment
        $month          = (int) date('n');
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
        $inputDate   = $request->input('date');

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
            'date-selector',
            'energy-suggestions',
            'energy-prediction-chart-electricity', // Behouden van SCRUM-53
            'energy-prediction-chart-gas',         // Behouden van SCRUM-53
        ];
    }

    public function saveSelectedMeter(Request $request)
    {
        $meterDatabaseId = $request->meter;
        $layout          = UserGridLayout::where('user_id', auth()->id())->first();

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

    /**
     * Get aggregated data for a date range
     *
     * @param string $meterId The meter ID
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Aggregated energy data
     */
    private function getAggregatedData(string $meterId, string $startDate, string $endDate): array
    {
        try {
            // Use getUsageBetweenDates method which already works correctly
            return $this->getUsageBetweenDates($meterId, $startDate, $endDate);
        } catch (\Exception $e) {
            Log::error('Error in getAggregatedData: ' . $e->getMessage());
            return ['energy_consumed' => 0, 'gas_delivered' => 0];
        }
    }

/**
 * Get usage between two dates (first and last readings)
 */
/**
 * Calculate target for a specific period using meter-specific budget
 */
    private function calculateMeterTargetForPeriod(string $type, string $period, string $date, \App\Models\EnergyBudget $meterBudget): float
    {
        $targetField  = $type === 'electricity' ? 'electricity_target_kwh' : 'gas_target_m3';
        $yearlyTarget = $meterBudget->$targetField ?? 0;
        $dateObj      = Carbon::parse($date);
        $currentMonth = $dateObj->month;

        // Get monthly budget for current month
        $monthlyBudget = $meterBudget->monthlyBudgets->firstWhere('month', $currentMonth);
        $monthlyTarget = $monthlyBudget ? $monthlyBudget->$targetField : ($yearlyTarget / 12);

        switch ($period) {
            case 'day':
                $daysInMonth = $dateObj->daysInMonth;
                return $monthlyTarget / $daysInMonth;

            case 'month':
                return $monthlyTarget;

            case 'year':
                $currentYear = Carbon::now()->year;
                if ($dateObj->year == $currentYear) {
                    $dayOfYear  = Carbon::now()->dayOfYear;
                    $daysInYear = Carbon::now()->isLeapYear() ? 366 : 365;
                    return $yearlyTarget * ($dayOfYear / $daysInYear);
                }
                return $yearlyTarget;

            default:
                return $monthlyTarget;
        }
    }

    private function getUsageBetweenDates(string $meterId, string $startDate, string $endDate): array
    {
        $startTime = Carbon::parse($startDate)->startOfDay()->toIso8601ZuluString();
        $endTime   = Carbon::parse($endDate)->endOfDay()->toIso8601ZuluString();

        // Query for first reading in period
        $firstQuery = '
    from(bucket: "' . config('influxdb.bucket') . '")
    |> range(start: ' . $startTime . ', stop: ' . $endTime . ')
    |> filter(fn: (r) => r["signature"] == "' . $meterId . '")
    |> filter(fn: (r) => r["_field"] == "gas_delivered" or r["_field"] == "energy_consumed")
    |> filter(fn: (r) => r["_measurement"] == "dsmr")
    |> first()
    ';

        // Query for last reading in period
        $lastQuery = '
    from(bucket: "' . config('influxdb.bucket') . '")
    |> range(start: ' . $startTime . ', stop: ' . $endTime . ')
    |> filter(fn: (r) => r["signature"] == "' . $meterId . '")
    |> filter(fn: (r) => r["_field"] == "gas_delivered" or r["_field"] == "energy_consumed")
    |> filter(fn: (r) => r["_measurement"] == "dsmr")
    |> last()
    ';

        try {
            $firstResult = $this->influxService->query($firstQuery);
            $lastResult  = $this->influxService->query($lastQuery);

            // Extract values
            $firstElectricity = 0;
            $firstGas         = 0;
            $lastElectricity  = 0;
            $lastGas          = 0;

            // Process first results
            if (! empty($firstResult)) {
                foreach ($firstResult as $table) {
                    if (! isset($table->records) || empty($table->records)) {
                        continue;
                    }

                    foreach ($table->records as $record) {
                        if (isset($record->values['_field']) && isset($record->values['_value'])) {
                            if ($record->values['_field'] === 'energy_consumed') {
                                $firstElectricity = (float) $record->values['_value'];
                            } else if ($record->values['_field'] === 'gas_delivered') {
                                $firstGas = (float) $record->values['_value'];
                            }
                        }
                    }
                }
            }

            // Process last results
            if (! empty($lastResult)) {
                foreach ($lastResult as $table) {
                    if (! isset($table->records) || empty($table->records)) {
                        continue;
                    }

                    foreach ($table->records as $record) {
                        if (isset($record->values['_field']) && isset($record->values['_value'])) {
                            if ($record->values['_field'] === 'energy_consumed') {
                                $lastElectricity = (float) $record->values['_value'];
                            } else if ($record->values['_field'] === 'gas_delivered') {
                                $lastGas = (float) $record->values['_value'];
                            }
                        }
                    }
                }
            }

            // Calculate differences
            $electricityUsage = max(0, $lastElectricity - $firstElectricity);
            $gasUsage         = max(0, $lastGas - $firstGas);

            return [
                'energy_consumed' => $electricityUsage,
                'gas_delivered'   => $gasUsage,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting usage between dates: ' . $e->getMessage());
            return ['energy_consumed' => 0, 'gas_delivered' => 0];
        }
    }

/**
 * Build a custom InfluxDB query for a date range
 */
    private function buildCustomRangeQuery(string $meterId, string $startDate, string $endDate): string
    {
        $startTime = Carbon::parse($startDate)->startOfDay()->toIso8601ZuluString();
        $endTime   = Carbon::parse($endDate)->endOfDay()->toIso8601ZuluString();

        // The error is in the sum() function which expects a single column name, not an array
        // Let's correct the query syntax
        return '
    from(bucket: "' . config('influxdb.bucket') . '")
    |> range(start: ' . $startTime . ', stop: ' . $endTime . ')
    |> filter(fn: (r) => r["signature"] == "' . $meterId . '")
    |> filter(fn: (r) => r["_field"] == "gas_delivered" or r["_field"] == "energy_consumed")
    |> filter(fn: (r) => r["_measurement"] == "dsmr")
    |> aggregateWindow(every: 1d, fn: last, createEmpty: false)
    |> difference()
    |> pivot(rowKey:["_time"], columnKey:["_field"], valueColumn:"_value")
    |> group()
    |> sum()
    ';
    }

    /**
     * Refresh data from InfluxDB to MySQL
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshData(Request $request)
    {
        $user            = Auth::user();
        $period          = session('dashboard_period', 'day');
        $date            = session('dashboard_date', Carbon::today()->format('Y-m-d'));
        $selectedMeterId = session('selected_meter_id', null);

        if (! $selectedMeterId) {
            return redirect()->route('dashboard')
                ->with('error', 'Geen slimme meter geselecteerd!');
        }

        try {
            // Get InfluxDB service
            $influxService = app(\App\Services\InfluxDBService::class);

            // Force refresh chart data by fetching from InfluxDB directly
            $result = $influxService->storeEnergyDashboardData($selectedMeterId, $period, $date);

            // Force refresh status data by removing old MySQL records
            EnergyStatusData::where('meter_id', $selectedMeterId)
                ->where('period', $period)
                ->where('date', $date)
                ->delete();

            // Trigger a fresh fetch by calling getLiveInfluxData (which will now store to MySQL)
            $this->getLiveInfluxData($selectedMeterId, $period, $date);

            return redirect()->route('dashboard')
                ->with('status', 'Data succesvol vernieuwd! Laatste update: ' . Carbon::now()->format('H:i:s'));
        } catch (\Exception $e) {
            \Log::error('Error refreshing data: ' . $e->getMessage());
            return redirect()->route('dashboard')
                ->with('error', 'Fout bij het vernieuwen van data: ' . $e->getMessage());
        }
    }
}
