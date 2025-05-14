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
        
        // Get yearly consumption to date - uses a cached or calculated value for better performance
        $yearlyConsumptionToDate = $this->getYearlyConsumptionToDate($type);
        
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
            // Voor jaarweergave, gebruik het jaarlijkse budget met pro-rate berekening
            // Dit geeft een eerlijker beeld van je voortgang gedurende het jaar
            $currentDayOfYear = $dateObj->dayOfYear;
            $daysInYear = $dateObj->isLeapYear() ? 366 : 365;
            $proRatedBudget = $yearlyTarget * ($currentDayOfYear / $daysInYear);
            $usagePercentage = ($actualUsageSoFar / $proRatedBudget) * 100;
        }
        
        $displayPercentage = round($usagePercentage, 1);
        
        // Calculate percentage for prediction relative to yearly/monthly budget
        // Verbeterde berekening met correctie voor periode
        if ($period === 'day') {
            // For day view, calculate against daily budget
            $dailyBudget = $monthlyBudgetValue / $dateObj->daysInMonth;
            $predictedTotal = $usageData['expected'];
            $exceedingPercentage = ($predictedTotal / $dailyBudget * 100) - 100;
        } else if ($period === 'month') {
            // For month view, calculate against monthly budget
            $predictedTotal = $usageData['expected'];
            $exceedingPercentage = ($predictedTotal / $monthlyBudgetValue * 100) - 100;
        } else {
            // For year view, calculate against yearly budget
            $predictedTotal = $usageData['expected'];
            $exceedingPercentage = ($predictedTotal / $yearlyTarget * 100) - 100;
        }
        
        // Calculate daily average consumption for informational purposes
        $daysPassedThisYear = max(1, Carbon::now()->dayOfYear);
        $dailyAverageConsumption = $yearlyConsumptionToDate / $daysPassedThisYear;
        
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
            'yearlyConsumptionToDate' => $yearlyConsumptionToDate,
            'dailyAverageConsumption' => $dailyAverageConsumption,
            'currentMonth' => $currentMonth,
            'daysInMonth' => $dateObj->daysInMonth,
            'proRatedBudget' => $yearlyTarget * ($currentMonth / 12)
        ]);
    }

    /**
     * Get yearly consumption to date - simulated for demo purposes
     * In a real implementation, this would come from a database query
     * 
     * @param string $type Energy type (electricity or gas)
     * @return float Yearly consumption to date
     */
    private function getYearlyConsumptionToDate(string $type): float
    {
        // In een echte implementatie haal je dit op uit de database
        // Voor nu gebruiken we een gesimuleerde waarde
        
        $currentDayOfYear = Carbon::now()->dayOfYear;
        $daysInYear = Carbon::now()->isLeapYear() ? 366 : 365;
        
        // Typisch jaarlijks verbruik met meer realistische waarden
        $yearlyTotal = $type === 'electricity' ? 3200 : 1400; // Reduced from 3500/1500
        
        // Simuleer realistische voortgang door het jaar met een niet-lineaire functie
        // Dit bootst beter na hoe energieverbruik gedurende het jaar varieert
        $progressFactor = $this->getNonLinearProgress($currentDayOfYear, $daysInYear);
        
        // Seizoensaanpassing (meer in winter, minder in zomer)
        $seasonalFactor = $this->getSeasonalAdjustment($type);
        
        // Voeg minieme willekeurige variatie toe voor realisme (±5%)
        $randomFactor = 0.97 + (mt_rand(0, 6) / 100);
        
        // Bereken geschat verbruik tot nu toe met wat afronding om "te perfecte" getallen te vermijden
        $consumption = $yearlyTotal * $progressFactor * $seasonalFactor * $randomFactor;
        return round($consumption * 100) / 100; // Afronden op 2 decimalen
    }
    
    /**
     * Berekent niet-lineaire voortgang gedurende het jaar
     * Dit simuleer beter echte gebruikspatronen dan een lineaire functie
     * 
     * @param int $currentDay Huidige dag van het jaar
     * @param int $totalDays Totaal aantal dagen in het jaar
     * @return float Voortgangsfactor (0-1)
     */
    private function getNonLinearProgress(int $currentDay, int $totalDays): float
    {
        // Gebruik een licht aangepaste sigmoid-functie om snellere groei in het midden van het jaar te simuleren
        // en langzamere groei aan het begin en einde van het jaar
        $x = ($currentDay / $totalDays) * 12 - 6; // Schalen naar -6 tot 6 voor sigmoid
        $sigmoid = 1 / (1 + exp(-$x));
        
        // Normaliseren naar 0-1 bereik
        $normalized = $sigmoid / (1 / (1 + exp(-6))); // Delen door maximale sigmoid waarde
        
        // Lineair component toevoegen voor realistischere curve
        $linear = $currentDay / $totalDays;
        
        // Combineer sigmoid (70%) met lineair (30%) voor een meer realistische curve
        return $normalized * 0.7 + $linear * 0.3;
    }
    
    /**
     * Bepaal seizoensfactor voor energieverbruik
     * 
     * @param string $type Energy type (electricity or gas)
     * @return float Seasonal adjustment factor
     */
    private function getSeasonalAdjustment(string $type): float
    {
        $month = (int)date('n');
        
        if ($type === 'gas') {
            // Gas heeft een sterk seizoensgebonden patroon
            $winterMonths = [1, 2, 3, 11, 12];
            $summerMonths = [6, 7, 8];
            
            if (in_array($month, $winterMonths)) {
                return 1.25; // Meer verbruik in winter (verlaagd van 1.3)
            } elseif (in_array($month, $summerMonths)) {
                return 0.75; // Minder verbruik in zomer (verhoogd van 0.7)
            }
            return 1.0;
        } else {
            // Elektriciteit heeft een milder seizoenspatroon
            // Gebruik een sinusoïdale functie voor geleidelijke overgangen tussen seizoenen
            return 1.0 + (cos(($month - 1) * 2 * M_PI / 12) * 0.1);
        }
    }
    
    /**
     * Get prediction data by processing historical data and generating forecast
     * 
     * @param string $period Period type (day, month, year)
     * @param string $date Reference date
     * @param string $type Energy type (electricity or gas)
     * @return array Tuple containing usage data and budget data
     */
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
        $currentMonthBudget = null;
        if (!empty($monthlyBudgets)) {
            foreach ($monthlyBudgets as $budget) {
                if ($budget->month == $currentMonth) {
                    $currentMonthBudget = $budget;
                    break;
                }
            }
        }
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
        
        // Get historical data for the period with improved patterns
        $historicalData = $this->getUsageDataByPeriod($period, $date, $type);
        
        // Get prediction based on type using the improved prediction service
        $predictionData = $type === 'electricity' 
            ? $this->predictionService->predictElectricityUsage($historicalData, $period)
            : $this->predictionService->predictGasUsage($historicalData, $period);
            
        // Format budget data for the chart with proper scaling
        $budgetTarget = $type === 'electricity' 
            ? ($energyBudget ? $energyBudget->electricity_target_kwh : 3500)
            : ($energyBudget ? $energyBudget->gas_target_m3 : 1500);
            
        $periodLength = $this->getPeriodLength($period, $dateObj);
        
        // Create budget lines with proper monthly values
        // Convert Collection to array to avoid type mismatch
        $budgetLine = $this->generateBudgetLine($period, $dateObj, $type, $budgetTarget, $currentMonthBudget, $monthlyBudgets->toArray(), $periodLength);
        
        // Post-process the prediction data to ensure best/worst cases are more realistic
        $processedPredictionData = $this->postProcessPredictionData($predictionData, $period, $dateObj, $type);
        
        $budgetData = [
            'target' => $budgetTarget,            // Totale jaarlijkse budget
            'monthly_target' => $monthlyBudgetValue, // Specifiek maandbudget voor de huidige maand
            'per_unit' => $budgetLine[0] ?? 0,    // Budget per eenheid (uur/dag/maand)
            'line' => $budgetLine,                // Lijn voor de grafiek
            'values' => $budgetLine               // Waarden voor berekeningen (hetzelfde als lijn)
        ];
        
        return [$processedPredictionData, $budgetData];
    }
    
    /**
     * Generate budget line for visualization based on period type
     * 
     * @param string $period Period type (day, month, year)
     * @param Carbon $dateObj Date object
     * @param string $type Energy type
     * @param float $budgetTarget Yearly budget target
     * @param ?MonthlyEnergyBudget $currentMonthBudget Current month's budget
     * @param array $monthlyBudgets All monthly budgets
     * @param int $periodLength Length of the period
     * @return array Budget line values
     */
    private function generateBudgetLine(
        string $period, 
        Carbon $dateObj, 
        string $type, 
        float $budgetTarget, 
        ?MonthlyEnergyBudget $currentMonthBudget, 
        array $monthlyBudgets, 
        int $periodLength
    ): array {
        $targetField = $type === 'electricity' ? 'electricity_target_kwh' : 'gas_target_m3';
        $budgetLine = [];
        
        switch ($period) {
            case 'day':
                // Voor dagweergave, bereken uurlijks budget uit het maandelijkse budget
                if ($currentMonthBudget) {
                    $daysInMonth = $dateObj->daysInMonth;
                    $dailyBudget = $currentMonthBudget->$targetField / $daysInMonth;
                    
                    // Varieer het uurlijkse budget op basis van typische dagpatronen
                    // Dit maakt de budget lijn realistischer (vs. een platte lijn)
                    $hourlyPatterns = $this->getDailyUsagePattern($dateObj->dayOfWeek);
                    $avgPattern = array_sum($hourlyPatterns) / count($hourlyPatterns);
                    
                    for ($i = 0; $i < $periodLength; $i++) {
                        // Schaal het budget op basis van typisch dagpatroon
                        // maar houd de totalen gelijk
                        $scaledBudget = ($dailyBudget / 24) * ($hourlyPatterns[$i] / $avgPattern);
                        $budgetLine[] = $scaledBudget;
                    }
                } else {
                    // Fallback als er geen maandelijks budget is
                    $dailyBudget = $budgetTarget / 365;
                    $hourlyBudget = $dailyBudget / 24;
                    $budgetLine = array_fill(0, $periodLength, $hourlyBudget);
                }
                break;
                
            case 'month':
                // Voor maandweergave, bereken dagelijks budget met weekdag-variatie
                if ($currentMonthBudget) {
                    $daysInMonth = $dateObj->daysInMonth;
                    $baseDailyBudget = $currentMonthBudget->$targetField / $daysInMonth;
                    
                    // Bepaal de stardag van de maand (0 = zondag, 6 = zaterdag)
                    $startDayOfWeek = Carbon::create($dateObj->year, $dateObj->month, 1)->dayOfWeek;
                    
                    // Weekdag variatie voor budget (weekend vs. weekdag)
                    $weekdayFactors = [
                        0 => 1.1,  // Zondag
                        1 => 0.95, // Maandag
                        2 => 0.95, // Dinsdag
                        3 => 0.95, // Woensdag
                        4 => 0.95, // Donderdag
                        5 => 1.0,  // Vrijdag
                        6 => 1.1   // Zaterdag
                    ];
                    
                    for ($i = 0; $i < $periodLength; $i++) {
                        $dayOfWeek = ($startDayOfWeek + $i) % 7;
                        $adjustedBudget = $baseDailyBudget * $weekdayFactors[$dayOfWeek];
                        $budgetLine[] = $adjustedBudget;
                    }
                } else {
                    // Fallback als er geen maandelijks budget is
                    $daysInMonth = $dateObj->daysInMonth;
                    $dailyBudget = ($budgetTarget / 12) / $daysInMonth;
                    $budgetLine = array_fill(0, $periodLength, $dailyBudget);
                }
                break;
                
            case 'year':
            default:
                // Voor jaarweergave, gebruik de daadwerkelijke maandelijkse budgetten
                if (!empty($monthlyBudgets)) {
                    for ($i = 0; $i < 12; $i++) {
                        $month = $i + 1;
                        $monthBudget = null;
                        
                        // Zoek het budget voor deze maand
                        foreach ($monthlyBudgets as $budget) {
                            if ($budget['month'] == $month) {
                                $monthBudget = $budget;
                                break;
                            }
                        }
                        
                        if ($monthBudget) {
                            $budgetLine[$i] = $monthBudget[$targetField];
                        } else {
                            // Fallback voor ontbrekende maanden met seizoensaanpassing
                            $seasonalFactor = $this->getMonthlySeasonalFactor($month, $type);
                            $adjustedMonthlyBudget = ($budgetTarget / 12) * $seasonalFactor;
                            $budgetLine[$i] = $adjustedMonthlyBudget;
                        }
                    }
                } else {
                    // Fallback met seizoensaanpassing als er geen maandelijkse budgetten zijn
                    for ($i = 0; $i < 12; $i++) {
                        $month = $i + 1;
                        $seasonalFactor = $this->getMonthlySeasonalFactor($month, $type);
                        $adjustedMonthlyBudget = ($budgetTarget / 12) * $seasonalFactor;
                        $budgetLine[$i] = $adjustedMonthlyBudget;
                    }
                }
                break;
        }
        
        return $budgetLine;
    }
    
    /**
     * Post-process prediction data to ensure best/worst case scenarios are realistic
     * 
     * @param array $predictionData Original prediction data
     * @param string $period Period type
     * @param Carbon $dateObj Date object
     * @param string $type Energy type
     * @return array Processed prediction data
     */
    private function postProcessPredictionData(array $predictionData, string $period, Carbon $dateObj, string $type): array
    {
        $currentPoint = $this->getCurrentPositionInPeriod($period, $dateObj);
        
        // Fix prediction margins for more realistic best/worst cases
        if ($period === 'year') {
            // Voor jaar: kleinere marges tussen best/worst, realistischer voor lange-termijn voorspelling
            $expectedTotal = $predictionData['expected'];
            $predictionData['best_case'] = $expectedTotal * 0.88; // 12% onder verwacht
            $predictionData['worst_case'] = $expectedTotal * 1.16; // 16% boven verwacht
        } else if ($period === 'month') {
            // Voor maand: redelijke marges tussen voorspellingen
            // De algoritmes in EnergyPredictionService zijn aangepast, dus 
            // we kunnen de resultaten grotendeels behouden met kleine correcties
            $expectedTotal = $predictionData['expected'];
            
            // Begrens de marges voor consistente weergave
            $bestCaseMargin = min(0.14, (100 - $predictionData['confidence']) / 400 + 0.08);
            $worstCaseMargin = min(0.24, (100 - $predictionData['confidence']) / 300 + 0.14);
            
            $predictionData['best_case'] = round($expectedTotal * (1 - $bestCaseMargin), 2);
            $predictionData['worst_case'] = round($expectedTotal * (1 + $worstCaseMargin), 2);
            
            // Werk de best/worst case lijnen bij als ze bestaan
            if (isset($predictionData['best_case_line']) && isset($predictionData['worst_case_line'])) {
                for ($i = $currentPoint + 1; $i < count($predictionData['best_case_line']); $i++) {
                    if ($predictionData['best_case_line'][$i] !== null) {
                        $predictionData['best_case_line'][$i] = $predictionData['prediction'][$i] * (1 - $bestCaseMargin);
                    }
                    if ($predictionData['worst_case_line'][$i] !== null) {
                        $predictionData['worst_case_line'][$i] = $predictionData['prediction'][$i] * (1 + $worstCaseMargin);
                    }
                }
            }
        } else if ($period === 'day') {
            // Voor dag: kleine marges voor korte-termijn voorspellingen
            $expectedTotal = $predictionData['expected'];
            
            if ($type === 'electricity') {
                // Electricity margins are slightly smaller due to more predictable usage
                $bestCaseMargin = 0.06; // 6% under expected
                $worstCaseMargin = 0.10; // 10% over expected
            } else {
                // Gas margins are slightly larger due to more variability
                $bestCaseMargin = 0.08; // 8% under expected
                $worstCaseMargin = 0.14; // 14% over expected
            }
            
            $predictionData['best_case'] = round($expectedTotal * (1 - $bestCaseMargin), 2);
            $predictionData['worst_case'] = round($expectedTotal * (1 + $worstCaseMargin), 2);
            
            // Update prediction lines
            if (isset($predictionData['best_case_line']) && isset($predictionData['worst_case_line'])) {
                for ($i = $currentPoint + 1; $i < count($predictionData['best_case_line']); $i++) {
                    if ($predictionData['best_case_line'][$i] !== null) {
                        $predictionData['best_case_line'][$i] = $predictionData['prediction'][$i] * (1 - $bestCaseMargin);
                    }
                    if ($predictionData['worst_case_line'][$i] !== null) {
                        $predictionData['worst_case_line'][$i] = $predictionData['prediction'][$i] * (1 + $worstCaseMargin);
                    }
                }
            }
        }
        
        return $predictionData;
    }
    
    /**
     * Calculate total prediction by combining actual data and prediction line
     * 
     * @param array $actualData Actual historical data
     * @param array $predictionData Prediction line data
     * @param int $currentPoint Current position in period
     * @return float Total (actual + predicted)
     */
    private function calculatePredictionTotal(array $actualData, array $predictionData, int $currentPoint): float
    {
        $total = 0;
        
        // Tel actuele waarden tot huidige punt
        for ($i = 0; $i <= $currentPoint; $i++) {
            if (isset($actualData[$i]) && $actualData[$i] !== null) {
                $total += $actualData[$i];
            }
        }
        
        // Tel voorspelde waarden na huidige punt
        for ($i = $currentPoint + 1; $i < count($predictionData); $i++) {
            if (isset($predictionData[$i]) && $predictionData[$i] !== null) {
                $total += $predictionData[$i];
            }
        }
        
        return $total;
    }
    
    /**
     * Get current position in period
     * 
     * @param string $period Period type
     * @param Carbon $dateObj Date object (optional)
     * @return int Current position (0-based index)
     */
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
     * Get monthly seasonal factor for budget calculation
     * 
     * @param int $month Month number (1-12)
     * @param string $type Energy type
     * @return float Seasonal factor
     */
    private function getMonthlySeasonalFactor(int $month, string $type): float
    {
        if ($type === 'gas') {
            // Gas heeft sterke seizoensinvloed
            $factors = [
                1 => 1.7, 2 => 1.6, 3 => 1.3, 4 => 1.0, 5 => 0.7, 6 => 0.5,
                7 => 0.4, 8 => 0.4, 9 => 0.6, 10 => 1.0, 11 => 1.4, 12 => 1.6
            ];
        } else {
            // Elektriciteit heeft mildere seizoensinvloed
            $factors = [
                1 => 1.15, 2 => 1.1, 3 => 1.05, 4 => 0.95, 5 => 0.9, 6 => 0.95,
                7 => 1.0, 8 => 1.0, 9 => 0.95, 10 => 1.0, 11 => 1.05, 12 => 1.1
            ];
        }
        
        return $factors[$month] ?? 1.0;
    }
    
    /**
     * Get typical daily usage pattern based on day of week
     * 
     * @param int $dayOfWeek Day of week (0 = Sunday, 6 = Saturday)
     * @return array Hourly pattern factors
     */
    private function getDailyUsagePattern(int $dayOfWeek): array
    {
        // Check if it's weekend
        $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
        
        if ($isWeekend) {
            // Weekend pattern - people wake up later, stay home more
            return [
                0.4, 0.3, 0.2, 0.2, 0.2, 0.3, // 0-5 night (very low)
                0.4, 0.6, 0.9, 1.2, 1.4, 1.3, // 6-11 morning (gradual rise)
                1.2, 1.2, 1.1, 1.2, 1.4, 1.6, // 12-17 afternoon (stays high)
                1.8, 1.8, 1.6, 1.3, 0.8, 0.5  // 18-23 evening (peak around 18-19)
            ];
        } else {
            // Weekday pattern - morning & evening peaks, less usage during workday
            return [
                0.4, 0.3, 0.2, 0.2, 0.2, 0.5, // 0-5 night (very low)
                0.9, 1.5, 1.7, 0.9, 0.6, 0.6, // 6-11 morning (peak at 7-8)
                0.6, 0.7, 0.6, 0.6, 0.8, 1.0, // 12-17 afternoon (gradual rise)
                1.8, 1.7, 1.4, 1.1, 0.7, 0.5  // 18-23 evening (peak at 18)
            ];
        }
    }
    
    /**
     * Get usage data for the period with improved patterns
     * 
     * @param string $period Period type
     * @param string $date Reference date
     * @param string $type Energy type
     * @return array Usage data
     */
    private function getUsageDataByPeriod(string $period, string $date, string $type): array
    {
        // In a real implementation this would fetch from database
        // For now we use simulated data with realistic patterns
        $dateObj = Carbon::parse($date);
        
        // Yearly household electricity is ~3500 kWh, gas is ~1500 m³
        // Slight reduction for more realistic totals
        $yearlyTotal = $type === 'electricity' ? 3200 : 1400;
        
        // Base values properly scaled for period
        $baseValues = [
            'day' => $yearlyTotal / 365,        // Daily base
            'month' => $yearlyTotal / 12,       // Monthly base
            'year' => $yearlyTotal              // Yearly total
        ];
        
        $baseValue = $baseValues[$period] ?? $baseValues['year'];
        $data = [];
        
        switch ($period) {
            case 'day':
                // Get hourly data with more realistic patterns
                $data = $this->generateDailyUsageData($dateObj, $baseValue, $type);
                break;
                
            case 'month':
                // Get daily data with improved patterns
                $data = $this->generateMonthlyUsageData($dateObj, $baseValue, $type);
                break;
                
            case 'year':
            default:
                // Get monthly data with seasonal patterns
                $data = $this->generateYearlyUsageData($dateObj, $baseValue, $type);
                break;
        }
        
        return $data;
    }
    
    /**
     * Generate realistic daily usage data with hourly patterns
     * 
     * @param Carbon $dateObj Date object
     * @param float $baseValue Base daily value
     * @param string $type Energy type
     * @return array Hourly usage data
     */
    private function generateDailyUsageData(Carbon $dateObj, float $baseValue, string $type): array
    {
        $dayOfWeek = $dateObj->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        $hourlyPatterns = $this->getDailyUsagePattern($dayOfWeek);
        
        // Add seasonal adjustment
        $month = $dateObj->month;
        $seasonalFactor = $this->getMonthlySeasonalFactor($month, $type);
        
        // Add day-specific variation
        $dayVariation = (($dateObj->day * 13) % 10) / 100 + 0.95;
        
        $currentHour = (int)date('G');
        $hourlyBase = $baseValue / 24 * $seasonalFactor * $dayVariation;
        
        $data = [];
        for ($i = 0; $i < 24; $i++) {
            if ($i <= $currentHour) {
                // Apply hourly pattern with slight randomization
                $hourlyValue = $hourlyBase * $hourlyPatterns[$i];
                
                // Add small random variation (±5%)
                $randomFactor = 0.95 + (mt_rand(0, 10) / 100);
                $data[] = round($hourlyValue * $randomFactor, 3);
            } else {
                $data[] = null; // Future hours are null
            }
        }
        
        return $data;
    }
    
    /**
     * Generate realistic monthly usage data with daily patterns
     * 
     * @param Carbon $dateObj Date object
     * @param float $baseValue Base monthly value
     * @param string $type Energy type
     * @return array Daily usage data
     */
    private function generateMonthlyUsageData(Carbon $dateObj, float $baseValue, string $type): array
    {
        $daysInMonth = $dateObj->daysInMonth;
        $currentDay = min((int)date('j'), $daysInMonth);
        $month = $dateObj->month;
        
        // Get start day of month (0 = Sunday, 6 = Saturday)
        $startDayOfWeek = Carbon::create($dateObj->year, $dateObj->month, 1)->dayOfWeek;
        
        // Define weekday patterns (weekend vs. workday usage)
        $weekdayFactors = [
            0 => 1.15, // Sunday
            1 => 0.95, // Monday
            2 => 0.95, // Tuesday
            3 => 0.95, // Wednesday
            4 => 0.95, // Thursday
            5 => 1.0,  // Friday
            6 => 1.2   // Saturday
        ];
        
        // Apply seasonal factor
        $seasonalFactor = $this->getMonthlySeasonalFactor($month, $type);
        
        // Determine if special events should be simulated
        $specialDays = $this->generateSpecialDays($daysInMonth, $type, $month);
        
        // Set base daily value
        $dailyBase = ($baseValue / $daysInMonth) * $seasonalFactor;
        
        $data = [];
        for ($i = 0; $i < $daysInMonth; $i++) {
            $dayOfMonth = $i + 1;
            
            if ($i < $currentDay) {
                // Calculate weekday for this day
                $dayOfWeek = ($startDayOfWeek + $i) % 7;
                
                // Apply weekday pattern
                $factor = $weekdayFactors[$dayOfWeek];
                
                // Apply special day factor if applicable
                if (isset($specialDays[$dayOfMonth])) {
                    $factor *= $specialDays[$dayOfMonth];
                }
                
                // Apply small random variation for realism
                $randomFactor = 0.95 + (mt_rand(0, 10) / 100);
                
                // Final value calculation
                $value = $dailyBase * $factor * $randomFactor;
                $data[] = round($value, 2);
            } else {
                $data[] = null; // Future days are null
            }
        }
        
        return $data;
    }
    
    /**
     * Generate special days for monthly simulation
     * 
     * @param int $daysInMonth Number of days in month
     * @param string $type Energy type
     * @param int $month Month number
     * @return array Map of day -> factor
     */
    private function generateSpecialDays(int $daysInMonth, string $type, int $month): array
    {
        $specialDays = [];
        
        // Generate a few random high-usage days
        $numEvents = mt_rand(2, 4); // 2-4 special days per month
        for ($i = 0; $i < $numEvents; $i++) {
            $day = mt_rand(1, $daysInMonth);
            $factor = 1.2 + (mt_rand(0, 15) / 100); // 20-35% more usage
            $specialDays[$day] = $factor;
        }
        
        // Simulate weather effects - more extreme in winter for gas
        if ($type === 'gas' && ($month <= 3 || $month >= 10)) {
            // Winter months - add cold snaps
            $numColdDays = mt_rand(1, 3); // 1-3 cold days
            for ($i = 0; $i < $numColdDays; $i++) {
                $day = mt_rand(1, $daysInMonth);
                $factor = 1.3 + (mt_rand(0, 20) / 100); // 30-50% more usage
                $specialDays[$day] = $factor;
            }
        } else if ($type === 'electricity' && ($month >= 6 && $month <= 8)) {
            // Summer months - add hot days for AC usage
            $numHotDays = mt_rand(1, 3); // 1-3 hot days
            for ($i = 0; $i < $numHotDays; $i++) {
                $day = mt_rand(1, $daysInMonth);
                $factor = 1.2 + (mt_rand(0, 15) / 100); // 20-35% more usage
                $specialDays[$day] = $factor;
            }
        }
        
        // Possible vacation period (20% chance)
        if (mt_rand(1, 5) === 1) {
            $startDay = mt_rand(1, $daysInMonth - 3);
            $duration = mt_rand(3, 7); // 3-7 days vacation
            for ($i = 0; $i < $duration && ($startDay + $i) <= $daysInMonth; $i++) {
                $specialDays[$startDay + $i] = 0.5; // 50% of normal usage
            }
        }
        
        return $specialDays;
    }
    
    /**
     * Generate realistic yearly usage data with monthly patterns
     * 
     * @param Carbon $dateObj Date object
     * @param float $baseValue Base yearly value
     * @param string $type Energy type
     * @return array Monthly usage data
     */
    private function generateYearlyUsageData(Carbon $dateObj, float $baseValue, string $type): array
    {
        $currentMonth = (int)date('n');
        
        // Get seasonal factors for each month
        $monthlyFactors = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyFactors[$i] = $this->getMonthlySeasonalFactor($i, $type);
        }
        
        // Monthly base value
        $monthlyBase = $baseValue / 12;
        
        $data = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $i + 1;
            
            if ($i < $currentMonth) {
                // Apply seasonal pattern
                $factor = $monthlyFactors[$month];
                
                // Add moderate random variation
                $randomFactor = 0.95 + (mt_rand(0, 10) / 100);
                
                // Calculate value with small rounding to avoid perfect numbers
                $value = $monthlyBase * $factor * $randomFactor;
                $data[] = round($value, 1);
            } else {
                $data[] = null; // Future months are null
            }
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