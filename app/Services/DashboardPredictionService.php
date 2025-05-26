<?php
namespace App\Services;

use App\Models\EnergyBudget;
use App\Models\MonthlyEnergyBudget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Helper service om voorspellingsgegevens voor de dashboard widgets te genereren
 */
class DashboardPredictionService
{
    private $predictionService;
    private $conversionService;

    /**
     * Constructor
     */
    public function __construct(
        EnergyPredictionService $predictionService,
        EnergyConversionService $conversionService
    ) {
        $this->predictionService = $predictionService;
        $this->conversionService = $conversionService;
    }

    /**
     * Genereer voorspellingsdata voor een dashboard widget
     *
     * @param string $type Type energie ('electricity' of 'gas')
     * @param string $period Periode ('day', 'month', 'year')
     * @param string $date Referentiedatum
     * @return array Voorspellingsdata voor de widget
     */
    public function getDashboardPredictionData(string $type, string $period, string $date): array
    {
        $dateObj = Carbon::parse($date);
        $userId  = Auth::id();

        // Haal de juiste budget data op
        $currentYear  = $dateObj->format('Y');
        $currentMonth = $dateObj->month;

        // Haal het energie budget op
        $energyBudget = EnergyBudget::where('year', $currentYear)
            ->where('user_id', $userId)
            ->latest()
            ->first();

        if (! $energyBudget) {
            // Fallback naar standaard waardes als er geen budget is
            return $this->getFallbackData($type, $period);
        }

        // Haal maandelijks budget op voor de huidige maand
        $monthlyBudget = MonthlyEnergyBudget::where('user_id', $userId)
            ->where('energy_budget_id', $energyBudget->id)
            ->where('month', $currentMonth)
            ->first();

        // Bepaal target veld op basis van type
        $targetField = $type === 'electricity' ? 'electricity_target_kwh' : 'gas_target_m3';

        // Haal de specifieke budget waarde op
        $monthlyBudgetValue = null;
        if ($monthlyBudget) {
            $monthlyBudgetValue = $monthlyBudget->$targetField;
        } else {
            // Fallback naar jaarbudget gedeeld door 12 als er geen maandbudget is
            $monthlyBudgetValue = $energyBudget ?
            ($type === 'electricity' ? $energyBudget->electricity_target_kwh / 12 : $energyBudget->gas_target_m3 / 12) :
            250; // Standaard fallback waarde als er geen budget bestaat
        }

        // Haal historische data voor de periode met verbeterde patronen
        $historicalData = $this->getUsageDataByPeriod($period, $date, $type);

        // Verkrijg voorspelling op basis van type
        $predictionData = $type === 'electricity'
        ? $this->predictionService->predictElectricityUsage($historicalData, $period)
        : $this->predictionService->predictGasUsage($historicalData, $period);

        // Formatteer budget data voor de grafiek
        $budgetTarget = $type === 'electricity'
        ? ($energyBudget ? $energyBudget->electricity_target_kwh : 3500)
        : ($energyBudget ? $energyBudget->gas_target_m3 : 1500);

        $periodLength = $this->getPeriodLength($period, $dateObj);

        // Maak budget lijnen met de juiste maandelijkse waardes
        // Zet Collection om naar array om type mismatch te voorkomen
        $monthlyBudgets = MonthlyEnergyBudget::where('user_id', $userId)
            ->where('energy_budget_id', $energyBudget->id)
            ->orderBy('month')
            ->get()
            ->toArray();

        $budgetLine = $this->generateBudgetLine($period, $dateObj, $type, $budgetTarget, $monthlyBudget, $monthlyBudgets, $periodLength);

        $budgetData = [
            'target'         => $budgetTarget,       // Totale jaarlijkse budget
            'monthly_target' => $monthlyBudgetValue, // Specifiek maandbudget voor de huidige maand
            'per_unit'       => $budgetLine[0] ?? 0, // Budget per eenheid (uur/dag/maand)
            'line'           => $budgetLine,         // Lijn voor de grafiek
            'values'         => $budgetLine,         // Waarden voor berekeningen (hetzelfde als lijn)
        ];

        return [
            'predictionData' => $predictionData,
            'budgetData'     => $budgetData,
            'confidence'     => $predictionData['confidence'] ?? 75,
        ];
    }
    public function getDashboardPredictionDataWithRealData(
        string $type,
        string $period,
        string $date,
        array $influxData
    ): array {
        // Extract the correct field from InfluxDB data based on type
        $actualDataKey = $type === 'electricity' ? 'energy_consumed' : 'gas_delivered';

        // Log what data we're working with
        \Log::debug("getDashboardPredictionDataWithRealData: Processing {$type} data, using {$actualDataKey} from InfluxDB");

        // Check if the data exists and is valid
        $actualData = null;
        if (isset($influxData['current_data'][$actualDataKey]) && is_array($influxData['current_data'][$actualDataKey])) {
            // Use the actual data from InfluxDB
            $actualData = $influxData['current_data'][$actualDataKey];
            \Log::debug("Found valid {$actualDataKey} data with " . count($actualData) . " points");
        } else {
            // Log the issue and fallback to simulated data
            \Log::warning("No valid {$actualDataKey} data found in InfluxDB response - using simulated data");
            \Log::debug("Available keys in influxData['current_data']: " . json_encode(isset($influxData['current_data']) ? array_keys($influxData['current_data']) : []));

            // Use simulated data instead
            return $this->getDashboardPredictionData($type, $period, $date);
        }

        $dateObj = Carbon::parse($date);
        $userId  = Auth::id();

        // Get the energy budget
        $currentYear  = $dateObj->format('Y');
        $currentMonth = $dateObj->month;

        $energyBudget = EnergyBudget::where('year', $currentYear)
            ->where('user_id', $userId)
            ->latest()
            ->first();

        if (! $energyBudget) {
            // Fallback to standard values if no budget exists
            return $this->getFallbackData($type, $period);
        }

        // Get monthly budget for the current month
        $monthlyBudget = MonthlyEnergyBudget::where('user_id', $userId)
            ->where('energy_budget_id', $energyBudget->id)
            ->where('month', $currentMonth)
            ->first();

        // Determine target field based on type
        $targetField = $type === 'electricity' ? 'electricity_target_kwh' : 'gas_target_m3';

        // Get the specific budget value
        $monthlyBudgetValue = null;
        if ($monthlyBudget) {
            $monthlyBudgetValue = $monthlyBudget->$targetField;
        } else {
            // Fallback to yearly budget divided by 12 if no monthly budget exists
            $monthlyBudgetValue = $energyBudget ?
            ($type === 'electricity' ? $energyBudget->electricity_target_kwh / 12 : $energyBudget->gas_target_m3 / 12) :
            250; // Default fallback value if no budget exists
        }

        // Generate prediction with the real data
        $predictionData = $type === 'electricity'
        ? $this->predictionService->predictElectricityUsage($actualData, $period)
        : $this->predictionService->predictGasUsage($actualData, $period);

        // Format budget data for the chart
        $budgetTarget = $type === 'electricity'
        ? ($energyBudget ? $energyBudget->electricity_target_kwh : 3500)
        : ($energyBudget ? $energyBudget->gas_target_m3 : 1500);

        $periodLength = $this->getPeriodLength($period, $dateObj);

        // Get monthly budgets for budget line generation
        $monthlyBudgets = MonthlyEnergyBudget::where('user_id', $userId)
            ->where('energy_budget_id', $energyBudget->id)
            ->orderBy('month')
            ->get()
            ->toArray();

        // Generate budget line with proper monthly values
        $budgetLine = $this->generateBudgetLine(
            $period,
            $dateObj,
            $type,
            $budgetTarget,
            $monthlyBudget,
            $monthlyBudgets,
            $periodLength
        );

        $budgetData = [
            'target'         => $budgetTarget,       // Total yearly budget
            'monthly_target' => $monthlyBudgetValue, // Specific monthly budget for the current month
            'per_unit'       => $budgetLine[0] ?? 0, // Budget per unit (hour/day/month)
            'line'           => $budgetLine,         // Line for the chart
            'values'         => $budgetLine,         // Values for calculations (same as line)
        ];

        return [
            'predictionData' => $predictionData,
            'budgetData'     => $budgetData,
            'confidence'     => $predictionData['confidence'] ?? 75,
        ];
    }
    /**
     * Genereer fallback voorspellingsdata als er geen budget data beschikbaar is
     */
    private function getFallbackData(string $type, string $period): array
    {
        // Genereer standaard historische data
        $historicalData = $this->getDefaultHistoricalData($type, $period);

        // Standaard voorspelling
        $predictionData = $type === 'electricity'
        ? $this->predictionService->predictElectricityUsage($historicalData, $period)
        : $this->predictionService->predictGasUsage($historicalData, $period);

        // Standaard budget waardes
        $defaultBudget        = $type === 'electricity' ? 3500 : 1500;
        $defaultMonthlyBudget = $defaultBudget / 12;

        // Standaard budget lijn
        $budgetLine = [];
        if ($period === 'day') {
            $budgetLine = array_fill(0, 24, $defaultBudget / 365 / 24);
        } else if ($period === 'month') {
            $daysInMonth = date('t');
            $budgetLine  = array_fill(0, $daysInMonth, $defaultMonthlyBudget / $daysInMonth);
        } else {
            $budgetLine = array_fill(0, 12, $defaultMonthlyBudget);
        }

        $budgetData = [
            'target'         => $defaultBudget,
            'monthly_target' => $defaultMonthlyBudget,
            'per_unit'       => $budgetLine[0] ?? 0,
            'line'           => $budgetLine,
            'values'         => $budgetLine,
        ];

        return [
            'predictionData' => $predictionData,
            'budgetData'     => $budgetData,
            'confidence'     => $predictionData['confidence'] ?? 70,
        ];
    }

    /**
     * Genereer standaard historische data
     */
    private function getDefaultHistoricalData(string $type, string $period): array
    {
        $data  = [];
        $count = $this->getPeriodLength($period, Carbon::now());

        // Genereer standaard patronen op basis van periode
        switch ($period) {
            case 'day':
                // Standaard dagpatroon (24 uur)
                $baseValue = $type === 'electricity' ? 0.4 : 0.2;
                for ($i = 0; $i < 24; $i++) {
                    // Ochtend en avondpiek
                    $factor = 1.0;
                    if ($i >= 7 && $i <= 9) {
                        $factor = 1.5;
                    }
                    // Ochtendpiek
                    if ($i >= 18 && $i <= 21) {
                        $factor = 1.8;
                    }
                    // Avondpiek
                    if ($i >= 0 && $i <= 5) {
                        $factor = 0.4;
                    }
                    // Nacht

                    $data[] = $baseValue * $factor * (0.9 + (mt_rand(0, 20) / 100));
                }
                break;

            case 'month':
                // Standaard maandpatroon (dagen)
                $daysInMonth = date('t');
                $baseValue   = $type === 'electricity' ? 8 : 4;
                for ($i = 0; $i < $daysInMonth; $i++) {
                    // Weekend vs. weekdag
                    $dayOfWeek = (date('w', strtotime(date('Y-m') . '-' . ($i + 1))) + 7) % 7;
                    $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
                    $factor    = $isWeekend ? 1.2 : 0.9;

                    $data[] = $baseValue * $factor * (0.9 + (mt_rand(0, 20) / 100));
                }
                break;

            case 'year':
                // Standaard jaarpatroon (maanden)
                $baseValue = $type === 'electricity' ? 250 : 120;
                for ($i = 0; $i < 12; $i++) {
                    // Seizoenspatroon
                    $factor = 1.0;
                    if ($i >= 0 && $i <= 2) {
                        $factor = 1.3;
                    }
                    // Winter (jan-mar)
                    if ($i >= 3 && $i <= 5) {
                        $factor = 0.9;
                    }
                    // Lente (apr-jun)
                    if ($i >= 6 && $i <= 8) {
                        $factor = $type === 'electricity' ? 1.1 : 0.6;
                    }
                    // Zomer (jul-sep)
                    if ($i >= 9 && $i <= 11) {
                        $factor = 1.2;
                    }
                    // Herfst (okt-dec)

                    $data[] = $baseValue * $factor * (0.9 + (mt_rand(0, 20) / 100));
                }
                break;
        }

        // Zet huidige en toekomstige data op null (we hebben alleen historische data)
        $currentPoint = $this->getCurrentPositionInPeriod($period);
        for ($i = $currentPoint; $i < count($data); $i++) {
            $data[$i] = null;
        }

        return $data;
    }

    /**
     * Get usage data for the period with improved patterns
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
            'day'   => $yearlyTotal / 365, // Daily base
            'month' => $yearlyTotal / 12,  // Monthly base
            'year'  => $yearlyTotal,       // Yearly total
        ];

        $baseValue = $baseValues[$period] ?? $baseValues['year'];
        $data      = [];

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
     */
    private function generateDailyUsageData(Carbon $dateObj, float $baseValue, string $type): array
    {
        $dayOfWeek      = $dateObj->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        $hourlyPatterns = $this->getDailyUsagePattern($dayOfWeek);

        // Add seasonal adjustment
        $month          = $dateObj->month;
        $seasonalFactor = $this->getMonthlySeasonalFactor($month, $type);

        // Add day-specific variation
        $dayVariation = (($dateObj->day * 13) % 10) / 100 + 0.95;

        $currentHour = (int) date('G');
        $hourlyBase  = $baseValue / 24 * $seasonalFactor * $dayVariation;

        $data = [];
        for ($i = 0; $i < 24; $i++) {
            if ($i <= $currentHour) {
                // Apply hourly pattern with slight randomization
                $hourlyValue = $hourlyBase * $hourlyPatterns[$i];

                // Add small random variation (±5%)
                $randomFactor = 0.95 + (mt_rand(0, 10) / 100);
                $data[]       = round($hourlyValue * $randomFactor, 3);
            } else {
                $data[] = null; // Future hours are null
            }
        }

        return $data;
    }

    /**
     * Generate realistic monthly usage data with daily patterns
     */
    private function generateMonthlyUsageData(Carbon $dateObj, float $baseValue, string $type): array
    {
        $daysInMonth = $dateObj->daysInMonth;
        $currentDay  = min((int) date('j'), $daysInMonth);
        $month       = $dateObj->month;

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
            6 => 1.2,  // Saturday
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
                $value  = $dailyBase * $factor * $randomFactor;
                $data[] = round($value, 2);
            } else {
                $data[] = null; // Future days are null
            }
        }

        return $data;
    }

    /**
     * Generate special days for monthly simulation
     */
    private function generateSpecialDays(int $daysInMonth, string $type, int $month): array
    {
        $specialDays = [];

                                    // Generate a few random high-usage days
        $numEvents = mt_rand(2, 4); // 2-4 special days per month
        for ($i = 0; $i < $numEvents; $i++) {
            $day               = mt_rand(1, $daysInMonth);
            $factor            = 1.2 + (mt_rand(0, 15) / 100); // 20-35% more usage
            $specialDays[$day] = $factor;
        }

        // Simulate weather effects - more extreme in winter for gas
        if ($type === 'gas' && ($month <= 3 || $month >= 10)) {
                                          // Winter months - add cold snaps
            $numColdDays = mt_rand(1, 3); // 1-3 cold days
            for ($i = 0; $i < $numColdDays; $i++) {
                $day               = mt_rand(1, $daysInMonth);
                $factor            = 1.3 + (mt_rand(0, 20) / 100); // 30-50% more usage
                $specialDays[$day] = $factor;
            }
        } else if ($type === 'electricity' && ($month >= 6 && $month <= 8)) {
                                         // Summer months - add hot days for AC usage
            $numHotDays = mt_rand(1, 3); // 1-3 hot days
            for ($i = 0; $i < $numHotDays; $i++) {
                $day               = mt_rand(1, $daysInMonth);
                $factor            = 1.2 + (mt_rand(0, 15) / 100); // 20-35% more usage
                $specialDays[$day] = $factor;
            }
        }

        return $specialDays;
    }

    /**
     * Generate realistic yearly usage data with monthly patterns
     */
    private function generateYearlyUsageData(Carbon $dateObj, float $baseValue, string $type): array
    {
        $currentMonth = (int) date('n');

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
                $value  = $monthlyBase * $factor * $randomFactor;
                $data[] = round($value, 1);
            } else {
                $data[] = null; // Future months are null
            }
        }

        return $data;
    }

    /**
     * Get typical daily usage pattern based on day of week
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
                1.8, 1.8, 1.6, 1.3, 0.8, 0.5, // 18-23 evening (peak around 18-19)
            ];
        } else {
            // Weekday pattern - morning & evening peaks, less usage during workday
            return [
                0.4, 0.3, 0.2, 0.2, 0.2, 0.5, // 0-5 night (very low)
                0.9, 1.5, 1.7, 0.9, 0.6, 0.6, // 6-11 morning (peak at 7-8)
                0.6, 0.7, 0.6, 0.6, 0.8, 1.0, // 12-17 afternoon (gradual rise)
                1.8, 1.7, 1.4, 1.1, 0.7, 0.5, // 18-23 evening (peak at 18)
            ];
        }
    }

    /**
     * Get monthly seasonal factor for budget calculation
     */
    private function getMonthlySeasonalFactor(int $month, string $type): float
    {
        if ($type === 'gas') {
            // Gas heeft sterke seizoensinvloed
            $factors = [
                1 => 1.7, 2 => 1.6, 3 => 1.3, 4  => 1.0, 5  => 0.7, 6  => 0.5,
                7 => 0.4, 8 => 0.4, 9 => 0.6, 10 => 1.0, 11 => 1.4, 12 => 1.6,
            ];
        } else {
            // Elektriciteit heeft mildere seizoensinvloed
            $factors = [
                1 => 1.15, 2 => 1.1, 3 => 1.05, 4  => 0.95, 5 => 0.9, 6   => 0.95,
                7 => 1.0, 8  => 1.0, 9 => 0.95, 10 => 1.0, 11 => 1.05, 12 => 1.1,
            ];
        }

        return $factors[$month] ?? 1.0;
    }

    /**
     * Get current position in period
     */
    private function getCurrentPositionInPeriod(string $period): int
    {
        switch ($period) {
            case 'day':
                return (int) date('G'); // Current hour (0-23)
            case 'month':
                return (int) date('j') - 1; // Current day (0-30)
            case 'year':
            default:
                return (int) date('n') - 1; // Current month (0-11)
        }
    }

    /**
     * Get the period length in number of units, accounting for the specific date
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
     * Generate budget line for visualization based on period type
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
        $budgetLine  = [];

        switch ($period) {
            case 'day':
                // Voor dagweergave, bereken uurlijks budget uit het maandelijkse budget
                if ($currentMonthBudget) {
                    $daysInMonth = $dateObj->daysInMonth;
                    $dailyBudget = $currentMonthBudget->$targetField / $daysInMonth;

                    // Varieer het uurlijkse budget op basis van typische dagpatronen
                    // Dit maakt de budget lijn realistischer (vs. een platte lijn)
                    $hourlyPatterns = $this->getDailyUsagePattern($dateObj->dayOfWeek);
                    $avgPattern     = array_sum($hourlyPatterns) / count($hourlyPatterns);

                    for ($i = 0; $i < $periodLength; $i++) {
                        // Schaal het budget op basis van typisch dagpatroon
                        // maar houd de totalen gelijk
                        $scaledBudget = ($dailyBudget / 24) * ($hourlyPatterns[$i] / $avgPattern);
                        $budgetLine[] = $scaledBudget;
                    }
                } else {
                    // Fallback als er geen maandelijks budget is
                    $dailyBudget  = $budgetTarget / 365;
                    $hourlyBudget = $dailyBudget / 24;
                    $budgetLine   = array_fill(0, $periodLength, $hourlyBudget);
                }
                break;

            case 'month':
                // Voor maandweergave, bereken dagelijks budget met weekdag-variatie
                if ($currentMonthBudget) {
                    $daysInMonth     = $dateObj->daysInMonth;
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
                        6 => 1.1,  // Zaterdag
                    ];

                    for ($i = 0; $i < $periodLength; $i++) {
                        $dayOfWeek      = ($startDayOfWeek + $i) % 7;
                        $adjustedBudget = $baseDailyBudget * $weekdayFactors[$dayOfWeek];
                        $budgetLine[]   = $adjustedBudget;
                    }
                } else {
                    // Fallback als er geen maandelijks budget is
                    $daysInMonth = $dateObj->daysInMonth;
                    $dailyBudget = ($budgetTarget / 12) / $daysInMonth;
                    $budgetLine  = array_fill(0, $periodLength, $dailyBudget);
                }
                break;

            case 'year':
            default:
                // Voor jaarweergave, gebruik de daadwerkelijke maandelijkse budgetten
                if (! empty($monthlyBudgets)) {
                    for ($i = 0; $i < 12; $i++) {
                        $month       = $i + 1;
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
                            $seasonalFactor        = $this->getMonthlySeasonalFactor($month, $type);
                            $adjustedMonthlyBudget = ($budgetTarget / 12) * $seasonalFactor;
                            $budgetLine[$i]        = $adjustedMonthlyBudget;
                        }
                    }
                } else {
                    // Fallback met seizoensaanpassing als er geen maandelijkse budgetten zijn
                    for ($i = 0; $i < 12; $i++) {
                        $month                 = $i + 1;
                        $seasonalFactor        = $this->getMonthlySeasonalFactor($month, $type);
                        $adjustedMonthlyBudget = ($budgetTarget / 12) * $seasonalFactor;
                        $budgetLine[$i]        = $adjustedMonthlyBudget;
                    }
                }
                break;
        }

        return $budgetLine;
    }
}
