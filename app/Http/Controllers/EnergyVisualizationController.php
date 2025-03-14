<?php

namespace App\Http\Controllers;

use App\Models\EnergyBudget;
use App\Services\EnergyConversionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class EnergyVisualizationController extends Controller
{
    private $conversionService;
    
    // Definieer de dagelijkse kosten per type woning voor gas
    private $gasCostsByHousingType = [
        'appartement' => 2.71,
        'tussenwoning' => 3.59,
        'hoekwoning' => 4.21,
        'twee_onder_een_kap' => 4.80,
        'vrijstaand' => 6.32
    ];
    
    // Realistische jaarlijkse targets per woning type
    private $yearlyTargets = [
        'appartement' => [
            'electricity_kwh' => 1900,
            'gas_m3' => 700
        ],
        'tussenwoning' => [
            'electricity_kwh' => 2500,
            'gas_m3' => 900
        ],
        'hoekwoning' => [
            'electricity_kwh' => 2800,
            'gas_m3' => 1050
        ],
        'twee_onder_een_kap' => [
            'electricity_kwh' => 3200,
            'gas_m3' => 1200
        ],
        'vrijstaand' => [
            'electricity_kwh' => 3800,
            'gas_m3' => 1500
        ]
    ];
    
    // Standaard woning type (kan later dynamisch worden ingesteld)
    private $housingType = 'tussenwoning';

    public function __construct(EnergyConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * Toon het dashboard voor energieverbruik.
     * 
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function dashboard(Request $request)
    {
        try {
            // Bepaal periode op basis van query parameter, standaard is maand
            $period = $request->query('period', 'month');
            $validPeriods = ['day', 'month', 'year'];
            
            if (!in_array($period, $validPeriods)) {
                $period = 'month';
            }
            
            // Optioneel woning type uit query
            if ($request->has('housing_type') && array_key_exists($request->query('housing_type'), $this->gasCostsByHousingType)) {
                $this->housingType = $request->query('housing_type');
            }

            // Huidige jaar voor het ophalen van het juiste budget
            $currentYear = date('Y');
            
            // Haal het energiebudget voor de huidige gebruiker op
            $budget = EnergyBudget::where('year', $currentYear)->latest()->first();
            
            // Als er geen budget is, toon een melding
            if (!$budget) {
                return view('energy.visualization.no-budget');
            }

            // Haal verbruiksgegevens op volgens de geselecteerde periode
            $usageData = $this->getUsageDataByPeriod($period);
            
            // Bereken totalen en percentages
            $totals = $this->calculateTotals($usageData, $budget, $period);
            
            // Bereid data voor voor grafieken
            $chartData = $this->prepareChartData($usageData, $totals, $period);

            return view('dashboard', [
                'period' => $period,
                'budget' => $budget,
                'usageData' => $usageData,
                'totals' => $totals,
                'chartData' => $chartData,
                'housingType' => $this->housingType,
                'gasCostsByHousingType' => $this->gasCostsByHousingType
            ]);
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
     * Haal verbruiksgegevens op volgens de geselecteerde periode.
     */
    private function getUsageDataByPeriod(string $period): array
    {
        // Haal de dagelijkse elektriciteitskosten en de dagelijkse gaskosten op
        $dailyElectricityCost = 1.97; // Euro per dag
        $dailyGasCost = $this->gasCostsByHousingType[$this->housingType];
        
        // Bereken kWh en m³ op basis van kosten en tarieven
        $dailyElectricityKwh = $this->conversionService->euroToKwh($dailyElectricityCost);
        $dailyGasM3 = $this->conversionService->euroToM3($dailyGasCost);
        
        // Demo data voor de geselecteerde periode
        $usageData = [];
        
        switch ($period) {
            case 'day':
                // Voorbeeld data voor uren van de dag
                // Verdeel het dagelijkse verbruik over de uren met realistische patronen
                $hourlyElectricityFactors = [
                    0.3, 0.2, 0.2, 0.2, 0.2, 0.3,  // 0-5u: laag verbruik 's nachts
                    0.7, 1.5, 1.8, 1.2, 0.8, 0.7,  // 6-11u: ochtendpiek
                    0.6, 0.6, 0.6, 0.6, 0.7, 1.2,  // 12-17u: middagverbruik
                    1.9, 2.0, 1.8, 1.5, 1.0, 0.5   // 18-23u: avondpiek
                ];
                
                $hourlyGasFactors = [
                    0.3, 0.2, 0.2, 0.2, 0.2, 0.3,  // 0-5u: laag verbruik 's nachts
                    1.0, 1.7, 1.5, 0.8, 0.5, 0.5,  // 6-11u: ochtendpiek (verwarming, douche)
                    0.5, 0.5, 0.5, 0.5, 0.7, 1.0,  // 12-17u: middagverbruik
                    1.8, 1.9, 1.5, 1.2, 0.8, 0.4   // 18-23u: avondpiek (verwarming, koken)
                ];
                
                // Normaliseer factors zodat ze optellen tot 24 (uren per dag)
                $electricityFactorSum = array_sum($hourlyElectricityFactors);
                $gasFactorSum = array_sum($hourlyGasFactors);
                
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
                $daysInMonth = date('t');
                
                // Definieer patronen voor weekdagen vs. weekend
                $weekdayElectricityFactor = 0.9;
                $weekendElectricityFactor = 1.3;
                
                $weekdayGasFactor = 0.9;
                $weekendGasFactor = 1.2;
                
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = date('Y-m-') . sprintf('%02d', $day);
                    $dayOfWeek = date('N', strtotime($date));
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
                        'label' => sprintf('%02d-%s', $day, date('m')),
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
                
                for ($month = 0; $month < 12; $month++) {
                    $daysInThisMonth = cal_days_in_month(CAL_GREGORIAN, $month + 1, date('Y'));
                    
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
        $yearlyElectricityTarget = $this->yearlyTargets[$this->housingType]['electricity_kwh'];
        $yearlyGasTarget = $this->yearlyTargets[$this->housingType]['gas_m3'];
        
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

    /**
     * Bepaal de status op basis van het percentage van het budget.
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
     * Bereid data voor voor grafieken.
     */
    private function prepareChartData(array $usageData, array $totals, string $period): array
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
        
        return [
            'labels' => $labels,
            'electricity' => [
                'data' => $electricityData,
                'target' => $electricityTargetLine
            ],
            'gas' => [
                'data' => $gasData,
                'target' => $gasTargetLine
            ],
            'cost' => [
                'electricity' => $electricityCostData,
                'gas' => $gasCostData
            ]
        ];
    }
}