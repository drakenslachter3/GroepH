<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EnergyPredictionService;
use App\Services\EnergyConversionService;
use Illuminate\Support\Facades\Auth;
use App\Models\EnergyBudget;
use Illuminate\View\View;

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
        $currentYear = date('Y', strtotime($date));
        $budget = EnergyBudget::where('year', $currentYear)
            ->where('user_id', Auth::user()->id)
            ->latest()
            ->first();
            
        if (!$budget) {
            return view('energy.no-budget');
        }
        
        // Get usage data for predictions
        list($usageData, $budgetData) = $this->getPredictionData($period, $date, $type);
        
        // Calculate percentage of budget used
        $target = $type === 'electricity' ? $budget->electricity_target_kwh : $budget->gas_target_m3;
        $usage = array_sum(array_filter($usageData['actual'], function($value) { return $value !== null; }));
        $percentage = ($usage / $target) * 100;
        
        return view('energy.predictions', [
            'usageData' => $usageData,
            'budgetData' => $budgetData,
            'period' => $period,
            'date' => $date,
            'type' => $type,
            'budget' => $budget,
            'percentage' => $percentage,
            'confidence' => $usageData['confidence'] ?? 75
        ]);
    }
    
    /**
     * Generate prediction data for the energy type, period and date
     * Ensuring proper budget scaling for each period
     *
     * @param string $period The period type (day, month, year)
     * @param string $date The reference date
     * @param string $type The energy type (electricity or gas)
     * @return array Array with usage data and budget data
     */
    private function getPredictionData(string $period, string $date, string $type): array
    {
        // Get current user's budget
        $currentYear = date('Y', strtotime($date));
        $budget = EnergyBudget::where('year', $currentYear)
            ->where('user_id', Auth::user()->id)
            ->latest()
            ->first();
            
        // Get historical data for the period
        $historicalData = $this->getUsageDataByPeriod($period, $date, $type);
        
        // Get prediction based on type
        $predictionData = $type === 'electricity' 
            ? $this->predictionService->predictElectricityUsage($historicalData, $period)
            : $this->predictionService->predictGasUsage($historicalData, $period);
            
        // Format budget data for the chart with proper scaling
        $budgetTarget = $type === 'electricity' 
            ? $budget->electricity_target_kwh 
            : $budget->gas_target_m3;
            
        $periodLength = $this->getPeriodLength($period);
        
        // Scale the budget appropriately for the period
        switch ($period) {
            case 'day':
                // For day view, show hourly budget allocation (yearly budget / 365 days / 24 hours)
                $budgetPerUnit = $budgetTarget / 365 / 24;
                break;
                
            case 'month':
                // For month view, show daily budget allocation (yearly budget / 365 days)
                // More precisely, use days in current month (yearly budget / 12 months / days in month)
                $daysInMonth = date('t', strtotime($date));
                $budgetPerUnit = $budgetTarget / 12 / $daysInMonth;
                break;
                
            case 'year':
            default:
                // For year view, show monthly budget allocation (yearly budget / 12 months)
                $budgetPerUnit = $budgetTarget / 12;
                break;
        }
        
        // Create budget lines scaled appropriately for the period view
        $budgetLine = array_fill(0, $periodLength, $budgetPerUnit);
        $budgetValues = array_fill(0, $periodLength, $budgetPerUnit);
        
        $budgetData = [
            'target' => $budgetTarget,            // Total yearly budget
            'per_unit' => $budgetPerUnit,         // Budget per unit (hour/day/month)
            'line' => $budgetLine,                // Line for chart
            'values' => $budgetValues             // Values for calculations
        ];
        
        return [$predictionData, $budgetData];
    }
    
    /**
     * Get the period length in number of units
     *
     * @param string $period Period type
     * @return int Length of period
     */
    private function getPeriodLength(string $period): int
    {
        switch ($period) {
            case 'day':
                return 24; // 24 hours
            case 'month':
                return date('t'); // Days in current month
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
                $daysInMonth = date('t', strtotime($date));
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