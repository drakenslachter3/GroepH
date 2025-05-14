<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EnergyPredictionService;
use App\Services\EnergyConversionService;
use Illuminate\Support\Facades\Auth;
use App\Models\EnergyBudget;
use App\Models\MonthlyEnergyBudget;
use Illuminate\View\View;
use Carbon\Carbon;

class EnergyPredictionController extends Controller
{
    private $predictionService;
    private $conversionService;

    /**
     * Create a new controller instance.
     *
     * @param EnergyPredictionService $predictionService
     * @param EnergyConversionService $conversionService
     */
    public function __construct(
        EnergyPredictionService $predictionService,
        EnergyConversionService $conversionService
    ) {
        $this->predictionService = $predictionService;
        $this->conversionService = $conversionService;
 
    }
    
  /**
 * Show the energy usage predictions.
 *
 * @param Request $request
 * @return \Illuminate\View\View
 */
public function showPredictions(Request $request): View
{
    // Determine period and date from request (similar to dashboard)
    $period = $request->query('period', 'year');
    if (!in_array($period, ['day', 'month', 'year'])) {
        $period = 'year';
    }

    $date = $request->query('date', date('Y-m-d'));
    $type = $request->query('type', 'electricity'); // 'electricity' or 'gas'
    
    // Get current user budget
    $dateObj = Carbon::parse($date);
    $currentYear = $dateObj->year;
    $currentMonth = $dateObj->month;
    $currentMonthName = $dateObj->format('F');
    
    $budget = EnergyBudget::where('year', $currentYear)
        ->where('user_id', Auth::user()->id)
        ->latest()
        ->first();
        
    if (!$budget) {
        return view('energy.no-budget');
    }
    
    // Get monthly budget for the current month
    $monthlyBudget = MonthlyEnergyBudget::where('energy_budget_id', $budget->id)
        ->where('month', $currentMonth)
        ->first();
        
    // Get the specific monthly budget amount
    $monthlyBudgetValue = null;
    if ($monthlyBudget) {
        $monthlyBudgetValue = $type === 'electricity' ? 
            $monthlyBudget->electricity_target_kwh : 
            $monthlyBudget->gas_target_m3;
    } else {
        // Fallback to yearly budget divided by 12 if no monthly budget exists
        $monthlyBudgetValue = $type === 'electricity' ?
            $budget->electricity_target_kwh / 12 :
            $budget->gas_target_m3 / 12;
    }
    
    // Get usage data for predictions
    list($usageData, $budgetData) = $this->getPredictionData($period, $date, $type);
    
    // Get the yearly target budget value
    $yearlyTarget = $type === 'electricity' ? $budget->electricity_target_kwh : $budget->gas_target_m3;
    
    // Calculate actual usage for the period so far
    $actualUsageSoFar = array_sum(array_filter($usageData['actual'], function($value) { 
        return $value !== null; 
    }));
    
    // CORRECTIE: Berekeningen voor binnen-budget percentage
    // Gebruik het juiste budget voor het percentage berekening op basis van de periode
    if ($period === 'month') {
        // Voor maandweergave, gebruik het maandelijkse budget
        $usagePercentage = ($actualUsageSoFar / $monthlyBudgetValue) * 100;
    } else if ($period === 'day') {
        // Voor dagweergave, gebruik het dagelijkse budget
        $dailyBudget = $monthlyBudgetValue / $dateObj->daysInMonth;
        $usagePercentage = ($actualUsageSoFar / $dailyBudget) * 100;
    } else {
        // Voor jaarweergave, gebruik het jaarlijkse budget
        $usagePercentage = ($actualUsageSoFar / $yearlyTarget) * 100;
    }
    
    $displayPercentage = round($usagePercentage, 1);
    
    // Calculate percentage for prediction relative to yearly/monthly budget
    if ($period === 'month') {
        // For month view, calculate against monthly budget
        $predictedTotal = $usageData['expected'];
        $exceedingPercentage = ($predictedTotal / $monthlyBudgetValue * 100) - 100;
    } else {
        // For year view, calculate against yearly budget
        $predictedTotal = $usageData['expected'];
        $exceedingPercentage = ($predictedTotal / $yearlyTarget * 100) - 100;
    }
    
    $isExceedingBudget = $exceedingPercentage > 0;
    $displayExceedingPercentage = round(abs($exceedingPercentage), 1);
    
    return view('energy.predictions', [
        'usageData' => $usageData,
        'budgetData' => $budgetData,
        'period' => $period,
        'date' => $date,
        'type' => $type,
        'budget' => $budget,
        // Added current month name and monthly budget
        'currentMonthName' => $currentMonthName,
        'monthlyBudgetValue' => $monthlyBudgetValue,
        // Used in the top indicator - shows progress so far
        'percentage' => $displayPercentage,
        // Used in the prediction cards - shows final prediction vs target
        'yearlyBudgetTarget' => $yearlyTarget,
        'isExceedingBudget' => $isExceedingBudget,
        'exceedingPercentage' => $displayExceedingPercentage,
        'confidence' => $usageData['confidence'] ?? 75,
        // Added data for details component
        'actualUsage' => $actualUsageSoFar,
        'currentMonth' => $currentMonth,
        'daysInMonth' => $dateObj->daysInMonth,
        'proRatedBudget' => $yearlyTarget * ($currentMonth / 12)
    ]);
}

    
    /**
     * Scale target budget based on the selected period, now using monthly budgets when available
     * 
     * @param float $yearlyTarget The yearly budget target
     * @param string $period The selected period (day, month, year)
     * @param string $date Reference date
     * @param string $type Energy type (electricity or gas)
     * @return float The scaled target for the period
     */
    private function getScaledTargetForPeriod(float $yearlyTarget, string $period, string $date, string $type): float
    {
        $dateObj = Carbon::parse($date);
        $currentMonth = (int)$dateObj->format('n');
        $userId = Auth::user()->id;
        
        // Get the user's yearly budget
        $energyBudget = EnergyBudget::where('year', $dateObj->format('Y'))
            ->where('user_id', $userId)
            ->latest()
            ->first();
            
        if (!$energyBudget) {
            // Fallback to default calculation if no budget exists
            switch ($period) {
                case 'day':
                    return $yearlyTarget / 365; // Daily target
                case 'month':
                    return $yearlyTarget / 12; // Monthly target
                default: // 'year'
                    return $yearlyTarget * ($currentMonth / 12); // Target so far this year
            }
        }
        
        // Try to get monthly budgets
        $monthlyBudgets = MonthlyEnergyBudget::where('user_id', $userId)
            ->where('energy_budget_id', $energyBudget->id)
            ->orderBy('month')
            ->get();
            
        if ($monthlyBudgets->isEmpty()) {
            // Fallback to default calculation if no monthly budgets exist
            switch ($period) {
                case 'day':
                    return $yearlyTarget / 365; // Daily target
                case 'month':
                    return $yearlyTarget / 12; // Monthly target
                default: // 'year'
                    return $yearlyTarget * ($currentMonth / 12); // Target so far this year
            }
        }
        
        // Get the field name for the target based on energy type
        $targetField = $type === 'electricity' ? 'electricity_target_kwh' : 'gas_target_m3';
        
        switch ($period) {
            case 'day':
                // Get daily budget from the monthly budget for the current month
                $monthlyBudget = $monthlyBudgets->firstWhere('month', $currentMonth);
                if ($monthlyBudget) {
                    $daysInMonth = $dateObj->daysInMonth;
                    return $monthlyBudget->$targetField / $daysInMonth; // Daily target
                } 
                return $yearlyTarget / 365; // Fallback
                
            case 'month':
                // Use the exact monthly budget for the selected month
                $selectedMonth = (int)$dateObj->format('n');
                $monthlyBudget = $monthlyBudgets->firstWhere('month', $selectedMonth);
                if ($monthlyBudget) {
                    return $monthlyBudget->$targetField; // Exact monthly target
                }
                return $yearlyTarget / 12; // Fallback
                
            case 'year':
            default:
                // For year view, calculate sum of monthly budgets up to current month
                $totalBudgetSoFar = 0;
                foreach ($monthlyBudgets as $budget) {
                    if ($budget->month <= $currentMonth) {
                        $totalBudgetSoFar += $budget->$targetField;
                    }
                }
                return $totalBudgetSoFar > 0 ? $totalBudgetSoFar : $yearlyTarget * ($currentMonth / 12);
        }
    }
    
    // Wijziging 1: In EnergyPredictionController.php
// Pas de getPredictionData methode aan om de maandelijkse budgetten correct door te geven

private function getPredictionData(string $period, string $date, string $type): array
{
    $dateObj = Carbon::parse($date);
    $userId = Auth::user()->id;
    
    // Get current user's budget
    $currentYear = $dateObj->format('Y');
    $currentMonth = $dateObj->month;
    
    $energyBudget = EnergyBudget::where('year', $currentYear)
        ->where('user_id', $userId)
        ->latest()
        ->first();
        
    // Get monthly budgets
    $monthlyBudgets = [];
    if ($energyBudget) {
        $monthlyBudgets = MonthlyEnergyBudget::where('user_id', $userId)
            ->where('energy_budget_id', $energyBudget->id)
            ->orderBy('month')
            ->get();
    }
    
    // Get monthly budget for the current month
    $currentMonthBudget = $monthlyBudgets->firstWhere('month', $currentMonth);
    $targetField = $type === 'electricity' ? 'electricity_target_kwh' : 'gas_target_m3';
    
    // Get the specific monthly budget amount
    $monthlyBudgetValue = null;
    if ($currentMonthBudget) {
        $monthlyBudgetValue = $currentMonthBudget->$targetField;
    } else {
        // Fallback to yearly budget divided by 12 if no monthly budget exists
        $monthlyBudgetValue = $energyBudget ?
            ($type === 'electricity' ? $energyBudget->electricity_target_kwh / 12 : $energyBudget->gas_target_m3 / 12) :
            250; // Default fallback value if no budget exists
    }
    
    // Get historical data for the period
    $historicalData = $this->getUsageDataByPeriod($period, $date, $type);
    
    // Get prediction based on type
    $predictionData = $type === 'electricity' 
        ? $this->predictionService->predictElectricityUsage($historicalData, $period)
        : $this->predictionService->predictGasUsage($historicalData, $period);
        
    // Format budget data for the chart with proper scaling
    $budgetTarget = $type === 'electricity' 
        ? $energyBudget->electricity_target_kwh 
        : $energyBudget->gas_target_m3;
        
    $periodLength = $this->getPeriodLength($period, $dateObj);
    
    // Create budget lines with proper monthly values
    $budgetLine = [];
    $budgetValues = [];
    
    switch ($period) {
        case 'day':
            // Voor dagweergave, bereken uurlijks budget uit het maandelijkse budget
            if ($currentMonthBudget) {
                $daysInMonth = $dateObj->daysInMonth;
                $dailyBudget = $currentMonthBudget->$targetField / $daysInMonth;
                $hourlyBudget = $dailyBudget / 24;
            } else {
                // Fallback als er geen maandelijks budget is
                $dailyBudget = $budgetTarget / 365;
                $hourlyBudget = $dailyBudget / 24;
            }
            
            $budgetLine = array_fill(0, $periodLength, $hourlyBudget);
            $budgetValues = array_fill(0, $periodLength, $hourlyBudget);
            break;
            
        case 'month':
            // Voor maandweergave, bereken dagelijks budget uit het maandelijkse budget
            if ($currentMonthBudget) {
                $daysInMonth = $dateObj->daysInMonth;
                $dailyBudget = $currentMonthBudget->$targetField / $daysInMonth;
            } else {
                // Fallback als er geen maandelijks budget is
                $daysInMonth = $dateObj->daysInMonth;
                $dailyBudget = ($budgetTarget / 12) / $daysInMonth;
            }
            
            $budgetLine = array_fill(0, $periodLength, $dailyBudget);
            $budgetValues = array_fill(0, $periodLength, $dailyBudget);
            break;
            
        case 'year':
        default:
            // Voor jaarweergave, gebruik de daadwerkelijke maandelijkse budgetten
            if ($monthlyBudgets->isNotEmpty()) {
                for ($i = 0; $i < 12; $i++) {
                    $month = $i + 1;
                    $monthBudget = $monthlyBudgets->firstWhere('month', $month);
                    
                    if ($monthBudget) {
                        $budgetLine[$i] = $monthBudget->$targetField;
                        $budgetValues[$i] = $monthBudget->$targetField;
                    } else {
                        // Fallback voor ontbrekende maanden
                        $budgetLine[$i] = $budgetTarget / 12;
                        $budgetValues[$i] = $budgetTarget / 12;
                    }
                }
            } else {
                // Fallback als er helemaal geen maandelijkse budgetten zijn
                $monthlyValue = $budgetTarget / 12;
                $budgetLine = array_fill(0, $periodLength, $monthlyValue);
                $budgetValues = array_fill(0, $periodLength, $monthlyValue);
            }
            break;
    }
    
    // Verbeterde resultaten voor maand/dag voorspellingen
    if ($period === 'month' || $period === 'day') {
        // Verbeter resultaten voor maandvoorspellingen
        $currentPoint = $this->getCurrentPositionInPeriod($period, $dateObj);
        
        // Verbeter best case/worst case voor maandweergave
        if (isset($predictionData['best_case_line']) && isset($predictionData['worst_case_line'])) {
            // Maak best case realistischer (dichterbij de verwachte trend)
            for ($i = $currentPoint + 1; $i < count($predictionData['best_case_line']); $i++) {
                if ($predictionData['best_case_line'][$i] !== null) {
                    // Best case: 20-30% lager dan verwachte trend voor maandweergave
                    $bestCaseFactor = mt_rand(70, 80) / 100;
                    $predictionData['best_case_line'][$i] = $predictionData['prediction'][$i] * $bestCaseFactor;
                }
            }
            
            // Maak worst case realistischer (niet te extreem)
            for ($i = $currentPoint + 1; $i < count($predictionData['worst_case_line']); $i++) {
                if ($predictionData['worst_case_line'][$i] !== null) {
                    // Worst case: 20-40% hoger dan verwachte trend voor maandweergave
                    $worstCaseFactor = mt_rand(120, 140) / 100;
                    $predictionData['worst_case_line'][$i] = $predictionData['prediction'][$i] * $worstCaseFactor;
                }
            }
            
            // Bereken nieuwe totalen voor best/worst case
            $bestCaseTotal = 0;
            $worstCaseTotal = 0;
            
            // Tel de waarden voor de actuele periode
            for ($i = 0; $i <= $currentPoint; $i++) {
                if ($predictionData['actual'][$i] !== null) {
                    $bestCaseTotal += $predictionData['actual'][$i];
                    $worstCaseTotal += $predictionData['actual'][$i];
                }
            }
            
            // Tel de voorspelde waarden voor de toekomstige periode
            for ($i = $currentPoint + 1; $i < count($predictionData['best_case_line']); $i++) {
                if ($predictionData['best_case_line'][$i] !== null) {
                    $bestCaseTotal += $predictionData['best_case_line'][$i];
                }
                if ($predictionData['worst_case_line'][$i] !== null) {
                    $worstCaseTotal += $predictionData['worst_case_line'][$i];
                }
            }
            
            // Update best/worst case totalen
            $predictionData['best_case'] = round($bestCaseTotal, 2);
            $predictionData['worst_case'] = round($worstCaseTotal, 2);
        }
    }
    
    $budgetData = [
        'target' => $budgetTarget,            // Totale jaarlijkse budget
        'monthly_target' => $monthlyBudgetValue, // Specifiek maandbudget voor de huidige maand
        'per_unit' => $budgetLine[0] ?? 0,    // Budget per eenheid (uur/dag/maand)
        'line' => $budgetLine,                // Lijn voor de grafiek
        'values' => $budgetValues             // Waarden voor berekeningen
    ];
    
    return [$predictionData, $budgetData];
}

private function getCurrentPositionInPeriod(string $period, Carbon $dateObj = null): int
{
    $dateObj = $dateObj ?? Carbon::now();
    
    switch ($period) {
        case 'day':
            return (int)$dateObj->format('G'); // Current hour (0-23)
        case 'month':
            return (int)$dateObj->format('j') - 1; // Current day (0-30)
        case 'year':
        default:
            return (int)$dateObj->format('n') - 1; // Current month (0-11)
    }
}

    
    /**
     * Get the period length in number of units, accounting for the specific date
     *
     * @param string $period Period type
     * @param Carbon $date Reference date
     * @return int Length of period
     */
    private function getPeriodLength(string $period, Carbon $date): int
    {
        switch ($period) {
            case 'day':
                return 24; // 24 hours
            case 'month':
                return $date->daysInMonth; // Days in the specific month
            case 'year':
            default:
                return 12; // 12 months
        }
    }
    
    /**
     * Get usage data for the period - with proper scaling between day/month/year views
     * 
     * @param string $period Period type
     * @param string $date Reference date
     * @param string $type Energy type
     * @return array Usage data for the period
     */
    private function getUsageDataByPeriod(string $period, string $date, string $type): array
    {
        // Simulated data generator based on realistic consumption patterns
        // In a real implementation this would fetch from database
        
        // Yearly household electricity is ~3500 kWh, gas is ~1500 m³
        $yearlyTotal = $type === 'electricity' ? 3500 : 1500;
        
        // Base values properly scaled for period
        $baseValues = [
            'day' => $yearlyTotal / 365,        // Daily base (9.6 kWh or 4.1 m³)
            'month' => $yearlyTotal / 12,       // Monthly base (292 kWh or 125 m³)
            'year' => $yearlyTotal              // Yearly total
        ];
        
        $baseValue = $baseValues[$period] ?? $baseValues['year'];
        
        // Generate data for the period
        $data = [];
        
        switch ($period) {
            case 'day':
                // Generate hourly data with realistic diurnal pattern
                $hourlyPatterns = [
                    0.4, 0.3, 0.2, 0.2, 0.2, 0.3, // 0-5 night (very low)
                    0.7, 1.4, 1.8, 1.2, 0.9, 0.8, // 6-11 morning (peak at 8)
                    0.9, 1.0, 0.9, 0.9, 1.1, 1.3, // 12-17 afternoon (gradual increase)
                    2.0, 1.9, 1.5, 1.1, 0.8, 0.5  // 18-23 evening (peak at 18)
                ];
                
                // Current hour - don't show future hours
                $currentHour = (int)date('G');
                
                // Hourly base (daily value / 24) but adjusted by hourly pattern
                $hourlyBase = $baseValue / 24;
                
                for ($i = 0; $i < 24; $i++) {
                    if ($i <= $currentHour) {
                        // Actual data with pattern and variation
                        $value = $hourlyBase * $hourlyPatterns[$i];
                        $data[] = $value + $this->randomVariation($value, 0.15);
                    } else {
                        // Future hours are null (will be filled by prediction)
                        $data[] = null;
                    }
                }
                break;
                
            case 'month':
                // Generate daily data with weekend pattern
                $dateObj = Carbon::parse($date);
                $daysInMonth = $dateObj->daysInMonth;
                $currentDay = min((int)date('j'), $daysInMonth);
                
                // Daily base (monthly value / days in month)
                $dailyBase = $baseValue / $daysInMonth;
                
                for ($i = 0; $i < $daysInMonth; $i++) {
                    if ($i < $currentDay) {
                        // Calculate day of week (1 = Monday, 7 = Sunday)
                        $dayOfWeek = date('N', strtotime(date('Y-m', strtotime($date)) . '-' . ($i + 1)));
                        
                        // Weekend multiplier (higher on weekends)
                        $weekendFactor = ($dayOfWeek >= 6) ? 1.3 : 1.0;
                        
                        // Type-specific adjustments
                        if ($type === 'gas') {
                            // Gas consumption tends to be more affected by external temperature
                            // Simulate some cold days with higher usage
                            $randomColdDay = (mt_rand(0, 100) < 15) ? 1.4 : 1.0;
                            $value = $dailyBase * $weekendFactor * $randomColdDay;
                        } else {
                            // Electricity has more consistent patterns with occasional spikes
                            $randomSpike = (mt_rand(0, 100) < 10) ? 1.3 : 1.0;
                            $value = $dailyBase * $weekendFactor * $randomSpike;
                        }
                        
                        $data[] = $value + $this->randomVariation($value, 0.1);
                    } else {
                        // Future days are null (will be filled by prediction)
                        $data[] = null;
                    }
                }
                break;
                
            case 'year':
            default:
                // Generate monthly data with seasonal variations
                $currentMonth = (int)date('n');
                
                // Define realistic seasonal patterns
                if ($type === 'gas') {
                    // Gas consumption has strong seasonal pattern (heating)
                    $monthlyPatterns = [
                        2.0, 1.8, 1.4, 1.0, 0.6, 0.4, // Jan-Jun: High in winter, tapering in spring
                        0.3, 0.3, 0.5, 0.9, 1.4, 1.8   // Jul-Dec: Low in summer, rising in fall
                    ];
                } else {
                    // Electricity has milder seasonal variations
                    $monthlyPatterns = [
                        1.3, 1.2, 1.1, 0.9, 0.8, 0.9,  // Jan-Jun: Higher in winter, lower in spring
                        1.0, 1.0, 0.9, 1.0, 1.1, 1.2   // Jul-Dec: Slight bump in summer, rising in fall
                    ];
                }
                
                // Monthly base (yearly total / 12) but adjusted by monthly pattern
                $monthlyBase = $baseValue / 12;
                
                for ($i = 0; $i < 12; $i++) {
                    if ($i < $currentMonth) {
                        // Actual data with pattern and variation
                        $value = $monthlyBase * $monthlyPatterns[$i];
                        
                        // Add some realistic month-to-month variation
                        $data[] = $value + $this->randomVariation($value, 0.08);
                    } else {
                        // Future months are null (will be filled by prediction)
                        $data[] = null;
                    }
                }
                break;
        }
        
        return $data;
    }
    
    /**
     * Add random variation to a value
     * 
     * @param float $value Base value
     * @param float $percentage Percentage of variation
     * @return float Value with random variation
     */
    private function randomVariation(float $value, float $percentage): float
    {
        $variation = $value * $percentage;
        return $value + mt_rand(-100, 100) / 100 * $variation;
    }
}