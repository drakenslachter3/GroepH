<?php

namespace App\Http\Controllers;

use App\Models\EnergyBudget;
use App\Services\EnergyConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EnergyVisualizationController extends Controller
{
    private $conversionService;

    public function __construct(EnergyConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * Toon het dashboard voor energieverbruik.
     */
    public function dashboard(Request $request): View
    {
        // Bepaal periode op basis van query parameter, standaard is maand
        $period = $request->query('period', 'month');
        $validPeriods = ['day', 'month', 'year'];
        
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
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

        return view('energy.visualization.dashboard', [
            'period' => $period,
            'budget' => $budget,
            'usageData' => $usageData,
            'totals' => $totals,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Haal verbruiksgegevens op volgens de geselecteerde periode.
     */
    private function getUsageDataByPeriod(string $period): array
    {
        // In een echte applicatie zou deze data uit de database komen
        // Voor deze demo maken we voorbeeld data aan
        
        $usageData = [];
        
        switch ($period) {
            case 'day':
                // Voorbeeld data voor uren van de dag
                for ($hour = 0; $hour < 24; $hour++) {
                    // Piek in ochtend en avond
                    $factor = 1;
                    if ($hour >= 7 && $hour <= 9) {
                        $factor = 1.5; // Ochtendpiek
                    } elseif ($hour >= 17 && $hour <= 21) {
                        $factor = 1.8; // Avondpiek
                    } elseif ($hour >= 0 && $hour <= 5) {
                        $factor = 0.3; // Nachtdal
                    }
                    
                    $usageData[] = [
                        'label' => sprintf('%02d:00', $hour),
                        'electricity_kwh' => round(0.8 * $factor + (mt_rand(0, 20) / 100), 2),
                        'gas_m3' => round(0.2 * $factor + (mt_rand(0, 10) / 100), 2)
                    ];
                }
                break;
                
            case 'month':
                // Voorbeeld data voor dagen van de maand
                $daysInMonth = date('t');
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    // Weekend vs. werkdag
                    $date = date('Y-m-') . sprintf('%02d', $day);
                    $dayOfWeek = date('N', strtotime($date));
                    $isWeekend = ($dayOfWeek >= 6);
                    $factor = $isWeekend ? 1.3 : 1.0;
                    
                    $usageData[] = [
                        'label' => sprintf('%02d-%s', $day, date('m')),
                        'electricity_kwh' => round(8.5 * $factor + (mt_rand(0, 200) / 100), 2),
                        'gas_m3' => round(2.2 * $factor + (mt_rand(0, 100) / 100), 2)
                    ];
                }
                break;
                
            case 'year':
                // Voorbeeld data voor maanden van het jaar
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                
                // Seizoensgebonden patronen (meer gas in winter, meer elektriciteit in zomer)
                $gasFactors = [1.8, 1.6, 1.4, 1.0, 0.7, 0.5, 0.4, 0.4, 0.6, 1.0, 1.3, 1.7];
                $electricityFactors = [1.0, 0.9, 0.9, 0.8, 0.8, 1.0, 1.2, 1.3, 1.0, 0.9, 0.9, 1.0];
                
                for ($month = 0; $month < 12; $month++) {
                    $usageData[] = [
                        'label' => $months[$month],
                        'electricity_kwh' => round(240 * $electricityFactors[$month] + (mt_rand(0, 2000) / 100), 2),
                        'gas_m3' => round(80 * $gasFactors[$month] + (mt_rand(0, 1000) / 100), 2)
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
        
        // Bepaal target op basis van periode
        // Voor dag en maand, bereken het proportionele deel van het jaarbudget
        $electricityTarget = $budget->electricity_target_kwh;
        $gasTarget = $budget->gas_target_m3;
        
        switch ($period) {
            case 'day':
                $daysInYear = date('L') ? 366 : 365;
                $electricityTarget = $electricityTarget / $daysInYear;
                $gasTarget = $gasTarget / $daysInYear;
                break;
                
            case 'month':
                $daysInMonth = date('t');
                $daysInYear = date('L') ? 366 : 365;
                $electricityTarget = $electricityTarget * ($daysInMonth / $daysInYear);
                $gasTarget = $gasTarget * ($daysInMonth / $daysInYear);
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
        $electricityCostData = array_map([$this->conversionService, 'kwhToEuro'], $electricityData);
        $gasCostData = array_map([$this->conversionService, 'm3ToEuro'], $gasData);
        
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