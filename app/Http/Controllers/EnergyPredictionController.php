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
            
        // Format budget data for the chart
        $budgetTarget = $type === 'electricity' 
            ? $budget->electricity_target_kwh 
            : $budget->gas_target_m3;
            
        $periodLength = $this->getPeriodLength($period);
        $budgetPerUnit = $budgetTarget / $periodLength;
        
        $budgetLine = array_fill(0, $periodLength, $budgetTarget);
        $budgetValues = array_fill(0, $periodLength, $budgetPerUnit);
        
        $budgetData = [
            'target' => $budgetTarget,
            'line' => $budgetLine,
            'values' => $budgetValues
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
     * Get usage data for the period
     * 
     * @param string $period Period type
     * @param string $date Reference date
     * @param string $type Energy type
     * @return array Usage data for the period
     */
    private function getUsageDataByPeriod(string $period, string $date, string $type): array
    {
        // Simulated data generator - in a real implementation this would fetch from database
        $dailyBaseValue = $type === 'electricity' ? 10 : 4; // Base daily usage
        
        // Generate data for the period
        $data = [];
        
        switch ($period) {
            case 'day':
                // Generate hourly data
                for ($i = 0; $i < 24; $i++) {
                    // Higher usage during morning (7-9) and evening (18-22)
                    $factor = ($i >= 7 && $i <= 9) || ($i >= 18 && $i <= 22) ? 1.5 : 1.0;
                    $value = $dailyBaseValue / 24 * $factor;
                    $data[] = $value + $this->randomVariation($value, 0.2);
                }
                break;
                
            case 'month':
                // Generate daily data
                $daysInMonth = date('t', strtotime($date));
                for ($i = 0; $i < $daysInMonth; $i++) {
                    // Higher usage on weekends
                    $dayOfWeek = date('N', strtotime(date('Y-m', strtotime($date)) . '-' . ($i + 1)));
                    $factor = ($dayOfWeek >= 6) ? 1.2 : 1.0;
                    $value = $dailyBaseValue * $factor;
                    $data[] = $value + $this->randomVariation($value, 0.15);
                }
                break;
                
            case 'year':
            default:
                // Generate monthly data
                for ($i = 0; $i < 12; $i++) {
                    // Seasonal variations
                    $month = $i + 1;
                    if ($type === 'gas') {
                        // Gas: higher in winter months
                        $factor = ($month >= 11 || $month <= 3) ? 1.8 : 
                                  (($month >= 4 && $month <= 5) || ($month >= 9 && $month <= 10) ? 1.2 : 0.7);
                    } else {
                        // Electricity: slightly higher in winter and summer
                        $factor = ($month >= 11 || $month <= 2) ? 1.3 : 
                                  (($month >= 6 && $month <= 8) ? 1.2 : 1.0);
                    }
                    $value = $dailyBaseValue * 30 * $factor; // Monthly total
                    $data[] = $value + $this->randomVariation($value, 0.1);
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