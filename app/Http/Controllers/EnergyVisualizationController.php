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
            return view('energy.error', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Haal verbruiksgegevens op volgens de geselecteerde periode en datum.
     */
    private function getUsageDataByPeriod(string $period, string $date): array
    {
        // Haal de dagelijkse elektriciteitskosten en de dagelijkse gaskosten op
        $dailyElectricityCost = 1.97; // Euro per dag
        $dailyGasCost = $this->gasCostsByHousingType[$this->housingType];

        // Bereken kWh en m³ op basis van kosten en tarieven
        $dailyElectricityKwh = $this->conversionService->euroToKwh($dailyElectricityCost);
        $dailyGasM3 = $this->conversionService->euroToM3($dailyGasCost);

        // Genereer data voor de geselecteerde periode
        $usageData = [];

        // Splitst datum in componenten
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        $day = date('d', strtotime($date));

        switch ($period) {
            case 'day':
                // Voorbeeld data voor uren van de dag
                // Verdeel het dagelijkse verbruik over de uren met realistische patronen
                $hourlyElectricityFactors = [
                    0.3,
                    0.2,
                    0.2,
                    0.2,
                    0.2,
                    0.3,  // 0-5u: laag verbruik 's nachts
                    0.7,
                    1.5,
                    1.8,
                    1.2,
                    0.8,
                    0.7,  // 6-11u: ochtendpiek
                    0.6,
                    0.6,
                    0.6,
                    0.6,
                    0.7,
                    1.2,  // 12-17u: middagverbruik
                    1.9,
                    2.0,
                    1.8,
                    1.5,
                    1.0,
                    0.5   // 18-23u: avondpiek
                ];

                $hourlyGasFactors = [
                    0.3,
                    0.2,
                    0.2,
                    0.2,
                    0.2,
                    0.3,  // 0-5u: laag verbruik 's nachts
                    1.0,
                    1.7,
                    1.5,
                    0.8,
                    0.5,
                    0.5,  // 6-11u: ochtendpiek (verwarming, douche)
                    0.5,
                    0.5,
                    0.5,
                    0.5,
                    0.7,
                    1.0,  // 12-17u: middagverbruik
                    1.8,
                    1.9,
                    1.5,
                    1.2,
                    0.8,
                    0.4   // 18-23u: avondpiek (verwarming, koken)
                ];

                // Normaliseer factors zodat ze optellen tot 24 (uren per dag)
                $electricityFactorSum = array_sum($hourlyElectricityFactors);
                $gasFactorSum = array_sum($hourlyGasFactors);

                // Pas de seed aan op basis van de datum voor consistente maar verschillende resultaten
                $seed = intval($year . $month . $day);
                mt_srand($seed);

                for ($hour = 0; $hour < 24; $hour++) {
                    $normalizedElectricityFactor = $hourlyElectricityFactors[$hour] * 24 / $electricityFactorSum;
                    $normalizedGasFactor = $hourlyGasFactors[$hour] * 24 / $gasFactorSum;

                    $hourlyElectricityKwh = ($dailyElectricityKwh / 24) * $normalizedElectricityFactor;
                    $hourlyGasM3 = ($dailyGasM3 / 24) * $normalizedGasFactor;

                    // Voeg wat kleine willekeurige variatie toe
                    $hourlyElectricityKwh *= (0.95 + (mt_rand(0, 10) / 100));
                    $hourlyGasM3 *= (0.95 + (mt_rand(0, 10) / 100));

                    $usageData[] = [
                        'label' => sprintf('%02d:00', $hour),
                        'electricity_kwh' => round($hourlyElectricityKwh, 2),
                        'gas_m3' => round($hourlyGasM3, 3)
                    ];
                }
                break;

            case 'month':
                // Voorbeeld data voor dagen van de maand
                $daysInMonth = date('t', strtotime($date));

                // Definieer patronen voor weekdagen vs. weekend
                $weekdayElectricityFactor = 0.9;
                $weekendElectricityFactor = 1.3;

                $weekdayGasFactor = 0.9;
                $weekendGasFactor = 1.2;

                // Pas de seed aan op basis van de datum
                $seed = intval($year . $month);
                mt_srand($seed);

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $currentDate = date('Y-m-d', strtotime("$year-$month-$day"));
                    $dayOfWeek = date('N', strtotime($currentDate));
                    $isWeekend = ($dayOfWeek >= 6);

                    $electricityFactor = $isWeekend ? $weekendElectricityFactor : $weekdayElectricityFactor;
                    $gasFactor = $isWeekend ? $weekendGasFactor : $weekdayGasFactor;

                    // Normaliseer zodat het gemiddelde overeenkomt met de dagelijkse doelwaarden
                    $monthlyElectricityAdjustment = $daysInMonth / (5 * $weekdayElectricityFactor + 2 * $weekendElectricityFactor);
                    $monthlyGasAdjustment = $daysInMonth / (5 * $weekdayGasFactor + 2 * $weekendGasFactor);

                    $dailyElectricityKwhAdjusted = $dailyElectricityKwh * $electricityFactor * $monthlyElectricityAdjustment;
                    $dailyGasM3Adjusted = $dailyGasM3 * $gasFactor * $monthlyGasAdjustment;

                    // Voeg wat willekeurige variatie toe
                    $dailyElectricityKwhAdjusted *= (0.9 + (mt_rand(0, 20) / 100));
                    $dailyGasM3Adjusted *= (0.9 + (mt_rand(0, 20) / 100));

                    $usageData[] = [
                        'label' => sprintf('%02d-%s', $day, $month),
                        'electricity_kwh' => round($dailyElectricityKwhAdjusted, 2),
                        'gas_m3' => round($dailyGasM3Adjusted, 3)
                    ];
                }
                break;

            case 'year':
                // Voorbeeld data voor maanden van het jaar
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

                // Seizoensgebonden patronen
                // Gas: hoog in winter, laag in zomer
                $gasSeasonalFactors = [1.8, 1.7, 1.4, 1.0, 0.7, 0.5, 0.4, 0.4, 0.6, 1.0, 1.5, 1.8];

                // Elektriciteit: redelijk consistent, iets hoger in winter (verlichting) en zomer (koeling)
                $electricitySeasonalFactors = [1.1, 1.0, 0.9, 0.9, 0.9, 1.0, 1.1, 1.1, 0.9, 1.0, 1.0, 1.1];

                // Normaliseer factoren zodat ze overeenkomen met het jaarlijkse gemiddelde
                $gasFactorSum = array_sum($gasSeasonalFactors);
                $electricityFactorSum = array_sum($electricitySeasonalFactors);

                // Pas de seed aan op basis van het jaar
                $seed = intval($year);
                mt_srand($seed);

                for ($month = 0; $month < 12; $month++) {
                    $daysInThisMonth = cal_days_in_month(CAL_GREGORIAN, $month + 1, $year);

                    $normalizedGasFactor = $gasSeasonalFactors[$month] * 12 / $gasFactorSum;
                    $normalizedElectricityFactor = $electricitySeasonalFactors[$month] * 12 / $electricityFactorSum;

                    $monthlyElectricityKwh = $dailyElectricityKwh * $daysInThisMonth * $normalizedElectricityFactor;
                    $monthlyGasM3 = $dailyGasM3 * $daysInThisMonth * $normalizedGasFactor;

                    // Voeg wat willekeurige variatie toe
                    $monthlyElectricityKwh *= (0.95 + (mt_rand(0, 10) / 100));
                    $monthlyGasM3 *= (0.95 + (mt_rand(0, 10) / 100));

                    $usageData[] = [
                        'label' => $months[$month],
                        'electricity_kwh' => round($monthlyElectricityKwh, 2),
                        'gas_m3' => round($monthlyGasM3, 2)
                    ];
                }
                break;
        }

        return $usageData;
    }

    /**
     * Haal historische verbruiksgegevens op voor vergelijkende analyses.
     */
    private function getHistoricalData(string $period, string $date): array
    {
        // In een echte implementatie zou dit historische data ophalen uit de database
        // Voor deze demo genereren we gesimuleerde historische data

        $currentDate = strtotime($date);
        $historicalData = [];

        switch ($period) {
            case 'day':
                // Haal gegevens op van dezelfde dag vorig jaar
                $lastYear = date('Y-m-d', strtotime('-1 year', $currentDate));
                $historicalData['last_year'] = $this->getUsageDataByPeriod($period, $lastYear);

                // Haal gegevens op van vorige dag
                $yesterday = date('Y-m-d', strtotime('-1 day', $currentDate));
                $historicalData['previous_day'] = $this->getUsageDataByPeriod($period, $yesterday);
                break;

            case 'month':
                // Haal gegevens op van dezelfde maand vorig jaar
                $lastYear = date('Y-m-d', strtotime('-1 year', $currentDate));
                $historicalData['last_year'] = $this->getUsageDataByPeriod($period, $lastYear);

                // Haal gegevens op van vorige maand
                $lastMonth = date('Y-m-d', strtotime('-1 month', $currentDate));
                $historicalData['previous_month'] = $this->getUsageDataByPeriod($period, $lastMonth);
                break;

            case 'year':
                // Haal gegevens op van vorig jaar
                $lastYear = date('Y-m-d', strtotime('-1 year', $currentDate));
                $historicalData['last_year'] = $this->getUsageDataByPeriod($period, $lastYear);

                // Haal gegevens op van 2 jaar geleden
                $twoYearsAgo = date('Y-m-d', strtotime('-2 years', $currentDate));
                $historicalData['two_years_ago'] = $this->getUsageDataByPeriod($period, $twoYearsAgo);
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
    private function prepareChartData(array $usageData, array $totals, string $period, array $historicalData): array
    {
        $labels = array_column($usageData, 'label');

        // Elektriciteitsverbruik data
        $electricityData = array_column($usageData, 'electricity_kwh');

        // Gasverbruik data
        $gasData = array_column($usageData, 'gas_m3');

        // Target lijnen (gemiddelde per periode-eenheid)
        $count = count($usageData);
        $avgElectricityTarget = $count > 0 ? $totals['electricity_target'] / $count : 0;
        $avgGasTarget = $count > 0 ? $totals['gas_target'] / $count : 0;

        $electricityTargetLine = array_fill(0, $count, round($avgElectricityTarget, 2));
        $gasTargetLine = array_fill(0, $count, round($avgGasTarget, 2));

        // Kosten data
        $electricityCostData = [];
        foreach ($electricityData as $kwh) {
            $electricityCostData[] = $this->conversionService->kwhToEuro($kwh);
        }

        $gasCostData = [];
        foreach ($gasData as $m3) {
            $gasCostData[] = $this->conversionService->m3ToEuro($m3);
        }

        // Historische vergelijking data
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

        // Trend data voor langetermijnanalyse
        $trendLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $trendElectricity = [210, 195, 180, 170, 165, 168, 172, 175, 168, 182, 190, 200];
        $trendGas = [120, 115, 90, 65, 40, 25, 20, 20, 35, 70, 100, 110];

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
                'electricity' => $trendElectricity,
                'gas' => $trendGas
            ]
        ];
    }
}
