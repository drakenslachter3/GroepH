<?php

namespace App\Http\Controllers;

use App\Models\EnergyBudget;
use App\Services\EnergyConversionService;
use App\Services\EnergyPredictionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class EnergyVisualizationController extends Controller
{
    private $conversionService;
    private $predictionService;

    // Definieer de dagelijkse kosten per type woning voor gas 
    //m2 ?? -lars
    private $gasCostsByHousingType = [
        'appartement' => 2.71,
        'tussenwoning' => 3.59,
        'hoekwoning' => 4.21,
        'twee_onder_een_kap' => 4.80,
        'vrijstaand' => 6.32
    ];

    // Standaard woning type (kan later dynamisch worden ingesteld)
    private $housingType = 'tussenwoning';

    public function __construct(EnergyConversionService $conversionService, EnergyPredictionService $predictionService)
    {
        $this->conversionService = $conversionService;
        $this->predictionService = $predictionService;
    }

    /**
     * Toon het dashboard voor energieverbruik.
     * 
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function dashboard(Request $request)
    {
        if (!Auth::check()) {
            return view('auth.login');
        }
        try {
            // Bepaal periode op basis van query parameter, standaard is maand
            $period = $request->query('period', 'month');
            $validPeriods = ['day', 'month', 'year'];

            if (!in_array($period, $validPeriods)) {
                $period = 'month';
            }

            // Bepaal datum (standaard is vandaag)
            $date = $request->query('date', date('Y-m-d'));

            // Optioneel woning type uit query
            if ($request->has('housing_type') && array_key_exists($request->query('housing_type'), $this->gasCostsByHousingType)) {
                $this->housingType = $request->query('housing_type');
            }

            // Huidige jaar voor het ophalen van het juiste budget
            $currentYear = date('Y', strtotime($date));
            try {
                $budget = EnergyBudget::where('year', $currentYear)
                    ->where('user_id', Auth::user()->id)
                    ->latest()
                    ->first();
            } catch (\Exception $e) {
                return view('/form');
            }
            // Haal het energiebudget voor de huidige gebruiker op


            // Als er geen budget is, toon een melding.
            //dit triggered niet als het leeg is -Lars
            if (!$budget || $budget == null) {
                return view('/form');
            }
            // Haal verbruiksgegevens op volgens de geselecteerde periode
            $usageData = $this->getUsageDataByPeriod($period, $date);

            // Haal historische gegevens op voor trends en voorspellingen
            $historicalData = $this->getHistoricalData($period, $date);

            // Voorspel toekomstig verbruik
            $electricityPrediction = $this->predictionService->predictElectricityUsage(
                array_column($usageData, 'electricity_kwh'),
                $period
            );

            $gasPrediction = $this->predictionService->predictGasUsage(
                array_column($usageData, 'gas_m3'),
                $period
            );

            // Genereer gepersonaliseerde besparingstips
            $savingTips = $this->predictionService->generateSavingTips(
                array_column($usageData, 'electricity_kwh'),
                array_column($usageData, 'gas_m3'),
                $period,
                $this->housingType
            );

            // Bereken totalen en percentages
            $totals = $this->calculateTotals($usageData, $budget, $period);

            // Voeg voorspellingen toe aan totalen
            $totals['electricity_prediction'] = $electricityPrediction;
            $totals['gas_prediction'] = $gasPrediction;

            // Bereid data voor voor grafieken
            $chartData = $this->prepareChartData($usageData, $totals, $period, $historicalData);

            $energy_data = [
                'period' => $period,
                'date' => $date,
                'budget' => $budget,
                'usageData' => $usageData,
                'historicalData' => $historicalData,
                'totals' => $totals,
                'chartData' => $chartData,
                'housingType' => $this->housingType,
                'gasCostsByHousingType' => $this->gasCostsByHousingType,
                'savingTips' => $savingTips,
                'conversionService' => $this->conversionService,
            ];

            return $energy_data;
        } catch (\Exception $e) {
            // Log de fout
            Log::error('EnergyVisualizationController error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            // Toon een eenvoudige foutpagina
            return view('energy.visualization.error', [
                'error' => $e->getMessage()
            ]);
        }
    }

   /**
 * Haal verbruiksgegevens op volgens de geselecteerde periode en datum.
 */
private function getUsageDataByPeriod(string $period, string $date): array
{
    // Get housing type gas costs
    $dailyGasCost = $this->gasCostsByHousingType[$this->housingType];
    
    // More realistic electricity usage values by housing type
    $electricityUsageByHousingType = [
        'appartement' => 5.7,     // ~5.7 kWh per day for apartment
        'tussenwoning' => 9.2,    // ~9.2 kWh per day for terraced house
        'hoekwoning' => 10.5,     // ~10.5 kWh per day for corner house
        'twee_onder_een_kap' => 12.8, // ~12.8 kWh per day for semi-detached house
        'vrijstaand' => 16.3,     // ~16.3 kWh per day for detached house
    ];
    
    // Get daily electricity usage based on housing type
    $dailyElectricityKwh = $electricityUsageByHousingType[$this->housingType] ?? 9.2;
    
    // More realistic gas usage values (in m3 per day) by housing type
    $gasUsageByHousingType = [
        'appartement' => 1.8,     // ~1.8 m3 per day for apartment
        'tussenwoning' => 2.4,    // ~2.4 m3 per day for terraced house
        'hoekwoning' => 2.9,      // ~2.9 m3 per day for corner house
        'twee_onder_een_kap' => 3.3, // ~3.3 m3 per day for semi-detached house
        'vrijstaand' => 4.4,      // ~4.4 m3 per day for detached house
    ];
    
    // Get daily gas usage based on housing type
    $dailyGasM3 = $gasUsageByHousingType[$this->housingType] ?? 2.4;
    
    // Calculate electricity cost from kWh
    $dailyElectricityCost = $this->conversionService->kwhToEuro($dailyElectricityKwh);
    
    // Genereer data voor de geselecteerde periode
    $usageData = [];
    
    // Splitst datum in componenten
    $year = date('Y', strtotime($date));
    $month = date('m', strtotime($date));
    $day = date('d', strtotime($date));

    switch ($period) {
        case 'day':
            // Realistic hourly patterns for electricity and gas
            // Based on typical Dutch household consumption patterns
            $hourlyElectricityFactors = [
                0.3, 0.2, 0.15, 0.15, 0.15, 0.25,  // 0-5u: low nighttime usage
                0.6, 1.3, 1.8, 1.1, 0.8, 0.9,      // 6-11u: morning peak (breakfast, leaving for work)
                0.7, 0.8, 0.7, 0.7, 0.9, 1.4,      // 12-17u: midday usage
                2.3, 2.5, 2.0, 1.4, 0.9, 0.5       // 18-23u: evening peak (dinner, TV, etc.)
            ];

            $hourlyGasFactors = [
                0.2, 0.15, 0.15, 0.15, 0.15, 0.2,  // 0-5u: minimal night usage
                1.2, 2.1, 1.2, 0.7, 0.5, 0.5,      // 6-11u: morning peak (heating, shower)
                0.6, 0.5, 0.4, 0.4, 0.6, 1.1,      // 12-17u: midday usage 
                2.3, 2.1, 1.4, 1.0, 0.6, 0.4       // 18-23u: evening peak (heating, cooking)
            ];

            // Apply seasonal variations for gas
            // Heating is used more in winter and less in summer
            $currentMonth = (int)$month;
            $seasonalGasMultiplier = $this->getSeasonalGasMultiplier($currentMonth);
            
            // Normalize hourly factors to maintain daily average
            $electricityFactorSum = array_sum($hourlyElectricityFactors);
            $gasFactorSum = array_sum($hourlyGasFactors);

            // Seed with consistent but different values based on date
            $seed = intval($year . $month . $day);
            mt_srand($seed);

            for ($hour = 0; $hour < 24; $hour++) {
                $normalizedElectricityFactor = $hourlyElectricityFactors[$hour] * 24 / $electricityFactorSum;
                $normalizedGasFactor = $hourlyGasFactors[$hour] * 24 / $gasFactorSum;

                // Calculate hourly values
                $hourlyElectricityKwh = ($dailyElectricityKwh / 24) * $normalizedElectricityFactor;
                
                // Apply seasonal multiplier for gas
                $hourlyGasM3 = ($dailyGasM3 / 24) * $normalizedGasFactor * $seasonalGasMultiplier;

                // Add small random variation (±5%)
                $randomFactor = 0.95 + (mt_rand(0, 100) / 1000);
                $hourlyElectricityKwh *= $randomFactor;
                
                $randomFactor = 0.95 + (mt_rand(0, 100) / 1000);
                $hourlyGasM3 *= $randomFactor;

                $usageData[] = [
                    'label' => sprintf('%02d:00', $hour),
                    'electricity_kwh' => round($hourlyElectricityKwh, 2),
                    'gas_m3' => round($hourlyGasM3, 3)
                ];
            }
            break;

        case 'month':
            $daysInMonth = date('t', strtotime($date));
            
            // Apply seasonal effect on monthly data
            $currentMonth = (int)$month;
            $seasonalGasMultiplier = $this->getSeasonalGasMultiplier($currentMonth);
            
            // Weekend vs weekday patterns
            $weekdayElectricityFactor = 0.9;  // Weekdays use less electricity (people at work)
            $weekendElectricityFactor = 1.3;  // Weekends use more (people at home)
            
            $weekdayGasFactor = 0.9;          // Weekdays lower gas (heating might be lowered during work)
            $weekendGasFactor = 1.25;         // Weekends higher (more cooking, heating all day)
            
            // Create random but consistent weather patterns that affect usage
            $seed = intval($year . $month);
            mt_srand($seed);
            
            // Initialize weather pattern for the month (cold snaps, warm spells)
            $weatherPattern = [];
            for ($i = 0; $i < $daysInMonth; $i++) {
                $weatherPattern[] = 0.85 + (mt_rand(0, 30) / 100);
            }
            
            // Create consecutive weather events (3-5 day patterns)
            $weatherEventLength = mt_rand(3, 5);
            for ($i = 0; $i < $daysInMonth; $i += $weatherEventLength) {
                $eventFactor = 0.85 + (mt_rand(0, 30) / 100);
                for ($j = 0; $j < $weatherEventLength && ($i + $j) < $daysInMonth; $j++) {
                    // Gradually change the factor to create smooth transitions
                    $weatherPattern[$i + $j] = $eventFactor + (($j / $weatherEventLength) * 0.15);
                }
                $weatherEventLength = mt_rand(3, 5); // New random length for next event
            }

            // Adjust for month normalization
            $weekdayCount = 0;
            $weekendCount = 0;
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = date('Y-m-d', strtotime("$year-$month-$day"));
                $dayOfWeek = date('N', strtotime($currentDate));
                if ($dayOfWeek >= 6) {
                    $weekendCount++;
                } else {
                    $weekdayCount++;
                }
            }
            
            $monthlyElectricityAdjustment = $daysInMonth / 
                (($weekdayCount * $weekdayElectricityFactor) + ($weekendCount * $weekendElectricityFactor));
            $monthlyGasAdjustment = $daysInMonth / 
                (($weekdayCount * $weekdayGasFactor) + ($weekendCount * $weekendGasFactor));

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = date('Y-m-d', strtotime("$year-$month-$day"));
                $dayOfWeek = date('N', strtotime($currentDate));
                $isWeekend = ($dayOfWeek >= 6);

                $electricityFactor = $isWeekend ? $weekendElectricityFactor : $weekdayElectricityFactor;
                $gasFactor = $isWeekend ? $weekendGasFactor : $weekdayGasFactor;

                // Apply weather effect (more significant for gas than electricity)
                $weatherEffect = $weatherPattern[$day - 1];
                
                $dailyElectricityKwhAdjusted = $dailyElectricityKwh * $electricityFactor * 
                    $monthlyElectricityAdjustment * (1 + (($weatherEffect - 1) * 0.3));
                
                $dailyGasM3Adjusted = $dailyGasM3 * $gasFactor * $monthlyGasAdjustment * 
                    $seasonalGasMultiplier * $weatherEffect;

                $usageData[] = [
                    'label' => sprintf('%02d-%s', $day, $month),
                    'electricity_kwh' => round($dailyElectricityKwhAdjusted, 2),
                    'gas_m3' => round($dailyGasM3Adjusted, 3)
                ];
            }
            break;

        case 'year':
            // More realistic seasonal patterns based on Dutch energy consumption
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            // Electricity seasonal factors
            // Higher in winter (more lighting, more indoor activities)
            // Higher in summer too (fans, some AC units in recent years)
            $electricitySeasonalFactors = [1.18, 1.10, 1.00, 0.92, 0.85, 0.80, 0.85, 0.90, 0.95, 1.05, 1.15, 1.25];
            
            // Gas seasonal factors - very seasonal due to heating
            // Winter is much higher than summer when gas is mainly used just for cooking/hot water
            $gasSeasonalFactors = [2.30, 2.10, 1.50, 1.00, 0.50, 0.30, 0.25, 0.25, 0.45, 0.95, 1.70, 2.20];
            
            // Yearly random variation to account for weather differences year to year
            $seed = intval($year);
            mt_srand($seed);
            
            // Add random yearly variation (cold or mild winters effect)
            $yearlyVariation = 0.9 + (mt_rand(0, 20) / 100);
            
            // Apply partial trending factors (efficiency improvements, behavior changes)
            // More recent years might show slightly lower consumption
            $efficiencyFactor = 1 - (min(max((int)$year - 2018, 0), 7) * 0.01); // 1% reduction per year after 2018
            
            for ($monthIndex = 0; $monthIndex < 12; $monthIndex++) {
                $daysInThisMonth = cal_days_in_month(CAL_GREGORIAN, $monthIndex + 1, $year);
                
                // Apply seasonal factors
                $monthlyElectricityKwh = $dailyElectricityKwh * $daysInThisMonth * 
                    $electricitySeasonalFactors[$monthIndex] * $efficiencyFactor;
                
                // Gas is more affected by yearly weather variations
                $gasVariation = ($monthIndex < 3 || $monthIndex > 8) 
                    ? $yearlyVariation  // More variation in winter months
                    : 1 + (($yearlyVariation - 1) * 0.3); // Less variation in summer
                
                $monthlyGasM3 = $dailyGasM3 * $daysInThisMonth * 
                    $gasSeasonalFactors[$monthIndex] * $gasVariation * $efficiencyFactor;
                
                // Add small random variation for reality
                $randomFactor = 0.95 + (mt_rand(0, 10) / 100);
                $monthlyElectricityKwh *= $randomFactor;
                
                $randomFactor = 0.95 + (mt_rand(0, 10) / 100);
                $monthlyGasM3 *= $randomFactor;

                $usageData[] = [
                    'label' => $months[$monthIndex],
                    'electricity_kwh' => round($monthlyElectricityKwh, 1),
                    'gas_m3' => round($monthlyGasM3, 1)
                ];
            }
            break;
    }

    return $usageData;
}

/**
 * Helper method to calculate seasonal gas multiplier
 * Gas usage is highly seasonal in the Netherlands
 */
private function getSeasonalGasMultiplier(int $month): float
{
    // Monthly multipliers where winter months use much more gas than summer
    $monthlyMultipliers = [
        1 => 2.3,  // January
        2 => 2.1,  // February
        3 => 1.5,  // March
        4 => 1.0,  // April
        5 => 0.5,  // May
        6 => 0.3,  // June
        7 => 0.25, // July
        8 => 0.25, // August
        9 => 0.45, // September
        10 => 0.95, // October
        11 => 1.7,  // November
        12 => 2.2   // December
    ];
    
    return $monthlyMultipliers[$month] ?? 1.0;
}

    /**
     * Haal historische verbruiksgegevens op voor vergelijkende analyses.
     */
  /**
 * Haal historische verbruiksgegevens op voor vergelijkende analyses.
 * This creates more realistic historical data with yearly trends and seasonal variations.
 */
private function getHistoricalData(string $period, string $date): array
{
    // In a real implementation, this would fetch data from the database
    // For this demo, we generate simulated historical data with realistic patterns
    
    $currentDate = strtotime($date);
    $historicalData = [];
    
    // Year-over-year efficiency trend - consumption tends to decrease slightly each year
    // due to more efficient appliances, better insulation, behavior changes, etc.
    $yearlyEfficiencyFactor = 0.97; // 3% less usage per year in the past
    
    switch ($period) {
        case 'day':
            // Get data from same day last year
            $lastYear = date('Y-m-d', strtotime('-1 year', $currentDate));
            $lastYearData = $this->getUsageDataByPeriod($period, $lastYear);
            
            // Apply yearly efficiency factor (people tend to use more energy in past years)
            foreach ($lastYearData as &$hourData) {
                $hourData['electricity_kwh'] = round($hourData['electricity_kwh'] / $yearlyEfficiencyFactor, 2);
                $hourData['gas_m3'] = round($hourData['gas_m3'] / $yearlyEfficiencyFactor, 3);
            }
            $historicalData['last_year'] = $lastYearData;
            
            // Get data from previous day
            $yesterday = date('Y-m-d', strtotime('-1 day', $currentDate));
            $historicalData['previous_day'] = $this->getUsageDataByPeriod($period, $yesterday);
            break;
            
        case 'month':
            // Get data from same month last year
            $lastYear = date('Y-m-d', strtotime('-1 year', $currentDate));
            $lastYearData = $this->getUsageDataByPeriod($period, $lastYear);
            
            // Apply yearly efficiency factor
            foreach ($lastYearData as &$dayData) {
                $dayData['electricity_kwh'] = round($dayData['electricity_kwh'] / $yearlyEfficiencyFactor, 2);
                $dayData['gas_m3'] = round($dayData['gas_m3'] / $yearlyEfficiencyFactor, 3);
            }
            $historicalData['last_year'] = $lastYearData;
            
            // Get data from previous month
            $lastMonth = date('Y-m-d', strtotime('-1 month', $currentDate));
            $historicalData['previous_month'] = $this->getUsageDataByPeriod($period, $lastMonth);
            break;
            
        case 'year':
            // Get data from last year
            $lastYear = date('Y-m-d', strtotime('-1 year', $currentDate));
            $lastYearData = $this->getUsageDataByPeriod($period, $lastYear);
            
            // Apply yearly efficiency factor
            foreach ($lastYearData as &$monthData) {
                $monthData['electricity_kwh'] = round($monthData['electricity_kwh'] / $yearlyEfficiencyFactor, 1);
                $monthData['gas_m3'] = round($monthData['gas_m3'] / $yearlyEfficiencyFactor, 1);
            }
            $historicalData['last_year'] = $lastYearData;
            
            // Get data from 2 years ago
            $twoYearsAgo = date('Y-m-d', strtotime('-2 years', $currentDate));
            $twoYearsAgoData = $this->getUsageDataByPeriod($period, $twoYearsAgo);
            
            // Apply efficiency factor twice for 2 years ago
            foreach ($twoYearsAgoData as &$monthData) {
                $monthData['electricity_kwh'] = round($monthData['electricity_kwh'] / ($yearlyEfficiencyFactor * $yearlyEfficiencyFactor), 1);
                $monthData['gas_m3'] = round($monthData['gas_m3'] / ($yearlyEfficiencyFactor * $yearlyEfficiencyFactor), 1);
            }
            $historicalData['two_years_ago'] = $twoYearsAgoData;
            break;
    }
    
    return $historicalData;
}
    /**
     * Bereken totalen en percentages op basis van verbruiksgegevens en budget.
     */
    private function calculateTotals(array $usageData, EnergyBudget $budget, string $period): array
    {
        // Bereken totaal verbruik
        $totalElectricityKwh = array_sum(array_column($usageData, 'electricity_kwh'));
        $totalGasM3 = array_sum(array_column($usageData, 'gas_m3'));

        // Bereken kosten
        $totalElectricityCost = $this->conversionService->kwhToEuro($totalElectricityKwh);
        $totalGasCost = $this->conversionService->m3ToEuro($totalGasM3);

        // Gebruik realistische targets op basis van woningtype
        $yearlyElectricityTarget = $budget->electricity_target_kwh;
        $yearlyGasTarget = $budget->gas_target_m3;

        // Bepaal target op basis van periode
        $electricityTarget = $yearlyElectricityTarget;
        $gasTarget = $yearlyGasTarget;

        switch ($period) {
            case 'day':
                $daysInYear = date('L') ? 366 : 365;
                $electricityTarget = $yearlyElectricityTarget / $daysInYear;
                $gasTarget = $yearlyGasTarget / $daysInYear;
                break;

            case 'month':
                $daysInMonth = date('t');
                $daysInYear = date('L') ? 366 : 365;
                $electricityTarget = $yearlyElectricityTarget * ($daysInMonth / $daysInYear);
                $gasTarget = $yearlyGasTarget * ($daysInMonth / $daysInYear);
                break;
        }

        // Bereken percentages t.o.v. target
        $electricityPercentage = $electricityTarget > 0 ? ($totalElectricityKwh / $electricityTarget) * 100 : 0;
        $gasPercentage = $gasTarget > 0 ? ($totalGasM3 / $gasTarget) * 100 : 0;

        // Bepaal status (goed, waarschuwing, kritiek)
        $electricityStatus = $this->determineStatus($electricityPercentage);
        $gasStatus = $this->determineStatus($gasPercentage);

        return [
            'electricity_kwh' => $totalElectricityKwh,
            'electricity_euro' => $totalElectricityCost,
            'electricity_target' => $electricityTarget,
            'electricity_percentage' => $electricityPercentage,
            'electricity_status' => $electricityStatus,

            'gas_m3' => $totalGasM3,
            'gas_euro' => $totalGasCost,
            'gas_target' => $gasTarget,
            'gas_percentage' => $gasPercentage,
            'gas_status' => $gasStatus,

            'total_euro' => $totalElectricityCost + $totalGasCost,
            'total_target_euro' => $this->conversionService->kwhToEuro($electricityTarget) + $this->conversionService->m3ToEuro($gasTarget),
            'period' => $period
        ];
    }

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
     * Bereid data voor voor grafieken, inclusief historische vergelijkingen.
     */
    /**
 * Bereid data voor voor grafieken, inclusief historische vergelijkingen.
 */
private function prepareChartData(array $usageData, array $totals, string $period, array $historicalData): array
{
    $labels = array_column($usageData, 'label');

    // Elektriciteitsverbruik data
    $electricityData = array_column($usageData, 'electricity_kwh');

    // Gasverbruik data
    $gasData = array_column($usageData, 'gas_m3');

    // Smarter target lines based on realistic seasonal patterns
    $count = count($usageData);
    
    // Base target on realistic monthly distribution for target lines
    $electricityTargetLine = [];
    $gasTargetLine = [];
    
    if ($period === 'year') {
        // For yearly view, create seasonally adjusted target lines
        $yearlyElectricityTarget = $totals['electricity_target'];
        $yearlyGasTarget = $totals['gas_target'];
        
        // Typical monthly distribution patterns for electricity in Netherlands
        $electricityMonthlyDistribution = [0.10, 0.09, 0.085, 0.078, 0.072, 0.068, 0.072, 0.076, 0.08, 0.087, 0.095, 0.107];
        
        // Typical monthly distribution patterns for gas in Netherlands (very seasonal)
        $gasMonthlyDistribution = [0.175, 0.160, 0.120, 0.080, 0.040, 0.023, 0.020, 0.020, 0.035, 0.075, 0.125, 0.167];
        
        for ($i = 0; $i < 12; $i++) {
            $electricityTargetLine[] = round($yearlyElectricityTarget * $electricityMonthlyDistribution[$i], 1);
            $gasTargetLine[] = round($yearlyGasTarget * $gasMonthlyDistribution[$i], 1);
        }
    } else {
        // For daily and monthly views, consider distributing the target evenly
        // but still account for patterns where appropriate
        
        // Base average targets
        $avgElectricityTarget = $count > 0 ? $totals['electricity_target'] / $count : 0;
        $avgGasTarget = $count > 0 ? $totals['gas_target'] / $count : 0;
        
        if ($period === 'day') {
            // For daily view, create hourly distribution of target
            // Higher during peak hours, lower during night
            $hourlyElectricityPattern = [
                0.5, 0.4, 0.3, 0.3, 0.3, 0.5,  // 0-5 hours
                0.8, 1.2, 1.5, 1.2, 0.9, 0.8,  // 6-11 hours
                0.8, 0.7, 0.7, 0.8, 1.0, 1.3,  // 12-17 hours
                1.8, 2.0, 1.7, 1.4, 1.0, 0.7   // 18-23 hours
            ];
            
            $hourlyGasPattern = [
                0.3, 0.2, 0.2, 0.2, 0.2, 0.4,  // 0-5 hours
                1.1, 1.8, 1.3, 0.7, 0.5, 0.5,  // 6-11 hours
                0.6, 0.5, 0.5, 0.6, 0.9, 1.2,  // 12-17 hours
                2.0, 1.8, 1.3, 0.9, 0.6, 0.4   // 18-23 hours
            ];
            
            // Normalize patterns
            $electricitySum = array_sum($hourlyElectricityPattern);
            $gasSum = array_sum($hourlyGasPattern);
            
            for ($i = 0; $i < 24; $i++) {
                $electricityTargetLine[] = round($avgElectricityTarget * $hourlyElectricityPattern[$i] * 24 / $electricitySum, 2);
                $gasTargetLine[] = round($avgGasTarget * $hourlyGasPattern[$i] * 24 / $gasSum, 3);
            }
        } elseif ($period === 'month') {
            // For monthly view, consider weekday vs weekend pattern
            $daysInMonth = count($usageData);
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf("%s-%02d", substr($usageData[0]['label'], 3), $day);
                $dayOfWeek = date('N', strtotime(date('Y-') . $date));
                $isWeekend = ($dayOfWeek >= 6);
                
                // Weekends tend to have higher consumption
                $electricityFactor = $isWeekend ? 1.25 : 0.9;
                $gasFactor = $isWeekend ? 1.2 : 0.95;
                
                $electricityTargetLine[] = round($avgElectricityTarget * $electricityFactor, 2);
                $gasTargetLine[] = round($avgGasTarget * $gasFactor, 3);
            }
        } else {
            // Fallback to even distribution
            $electricityTargetLine = array_fill(0, $count, round($avgElectricityTarget, 2));
            $gasTargetLine = array_fill(0, $count, round($avgGasTarget, 3));
        }
    }

    // Cost data based on current pricing
    $electricityCostData = [];
    foreach ($electricityData as $kwh) {
        $electricityCostData[] = $this->conversionService->kwhToEuro($kwh);
    }

    $gasCostData = [];
    foreach ($gasData as $m3) {
        $gasCostData[] = $this->conversionService->m3ToEuro($m3);
    }

    // Historical comparison data
    $historicalElectricity = [];
    $historicalGas = [];

    if (!empty($historicalData['last_year'])) {
        $historicalElectricity['last_year'] = array_column($historicalData['last_year'], 'electricity_kwh');
        $historicalGas['last_year'] = array_column($historicalData['last_year'], 'gas_m3');
    }

    if (isset($historicalData['previous_day'])) {
        $historicalElectricity['previous_period'] = array_column($historicalData['previous_day'], 'electricity_kwh');
        $historicalGas['previous_period'] = array_column($historicalData['previous_day'], 'gas_m3');
    } elseif (isset($historicalData['previous_month'])) {
        $historicalElectricity['previous_period'] = array_column($historicalData['previous_month'], 'electricity_kwh');
        $historicalGas['previous_period'] = array_column($historicalData['previous_month'], 'gas_m3');
    }

    // Trend data - use genuine seasonal patterns for Dutch energy consumption
    $trendLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    // Create a model of the yearly trend with seasonal variations for both electricity and gas
    // These values show a 3-year trend
    $currentYear = date('Y');
    
    // Realistic electricity trend for typical Dutch household (in kWh per month)
    // Higher in winter (lighting, indoor activities) and some increase in summer (fans)
    $baseElectricityMonth = [
        285, 260, 240, 220, 200, 190, 200, 210, 220, 240, 260, 290  // This year
    ];
    
    // Realistic gas trend for typical Dutch household (in m³ per month)
    // Much higher in winter months for heating, minimal in summer (just cooking and hot water)
    $baseGasMonth = [
        180, 165, 120, 80, 40, 25, 20, 20, 35, 75, 135, 175  // This year
    ];
    
    // Create trend data for this year and previous two years
    $trendElectricity = $baseElectricityMonth;
    $trendGas = $baseGasMonth;
    
    // Previous year (typically slightly higher consumption - ~3% efficiency improvement per year)
    $lastYearElectricity = array_map(function($value) {
        return round($value * 1.03);  // 3% higher last year
    }, $baseElectricityMonth);
    
    $lastYearGas = array_map(function($value) {
        return round($value * 1.03);  // 3% higher last year
    }, $baseGasMonth);
    
    // Two years ago (higher consumption)
    $twoYearAgoElectricity = array_map(function($value) {
        return round($value * 1.06);  // 6% higher two years ago
    }, $baseElectricityMonth);
    
    $twoYearAgoGas = array_map(function($value) {
        return round($value * 1.06);  // 6% higher two years ago
    }, $baseGasMonth);

    return [
        'labels' => $labels,
        'electricity' => [
            'data' => $electricityData,
            'target' => $electricityTargetLine,
            'historical' => $historicalElectricity
        ],
        'gas' => [
            'data' => $gasData,
            'target' => $gasTargetLine,
            'historical' => $historicalGas
        ],
        'cost' => [
            'electricity' => $electricityCostData,
            'gas' => $gasCostData
        ],
        'trend' => [
            'labels' => $trendLabels,
            'electricity' => [
                'thisYear' => $trendElectricity,
                'lastYear' => $lastYearElectricity,
                'twoYearsAgo' => $twoYearAgoElectricity
            ],
            'gas' => [
                'thisYear' => $trendGas,
                'lastYear' => $lastYearGas,
                'twoYearsAgo' => $twoYearAgoGas
            ]
        ]
    ];
}
}
