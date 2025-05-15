<?php
namespace App\Services;

class EnergyPredictionService
{
    /**
     * Voorspel het elektriciteitsverbruik voor de komende periode op basis van historische gegevens.
     *
     * @param array $historicalData Array van elektriciteitverbruik data
     * @param string $period 'day', 'month', of 'year'
     * @return array Voorspelde verbruik en bijbehorende informatie
     */
    public function predictElectricityUsage(array $historicalData, string $period): array
    {
        // Bereken trend op basis van de laatste 3 datapunten
        $recentData = array_slice($historicalData, -3);
        $trend      = $this->calculateTrend($recentData);

        // Bereken gemiddeld verbruik uit historische gegevens
        $averageUsage = $this->calculateAverage($historicalData);

        // Pas voorspelling aan op basis van seizoenspatronen
        $seasonalFactor = $this->getSeasonalFactor($period, 'electricity');

        // Bereken voorspelling
        $predictedUsage = $averageUsage * (1 + $trend) * $seasonalFactor;

        // Bereken voorspeld verbruik voor de komende periode
        $remainingFactor     = $this->getRemainingFactor($period);
        $predictedTotalUsage = 0;
        if($remainingFactor != 0){
            $predictedTotalUsage = $predictedUsage / $remainingFactor;
        }

        return [
            'predicted_usage'  => round($predictedUsage, 2),
            'predicted_total'  => round($predictedTotalUsage, 2),
            'trend_percentage' => round($trend * 100, 1),
            'trend_direction'  => $trend >= 0 ? 'up' : 'down',
            'confidence'       => $this->calculateConfidence($historicalData),
        ];
    }

    /**
     * Voorspel het gasverbruik voor de komende periode op basis van historische gegevens.
     *
     * @param array $historicalData Array van gasverbruik data
     * @param string $period 'day', 'month', of 'year'
     * @return array Voorspelde verbruik en bijbehorende informatie
     */
    public function predictGasUsage(array $historicalData, string $period): array
    {
        // Bereken trend op basis van de laatste 3 datapunten
        $recentData = array_slice($historicalData, -3);
        $trend      = $this->calculateTrend($recentData);

        // Bereken gemiddeld verbruik uit historische gegevens
        $averageUsage = $this->calculateAverage($historicalData);

        // Pas voorspelling aan op basis van seizoenspatronen
        $seasonalFactor = $this->getSeasonalFactor($period, 'gas');

        // Bereken voorspelling
        $predictedUsage = $averageUsage * (1 + $trend) * $seasonalFactor;

        // Bereken voorspeld verbruik voor de komende periode
        $remainingFactor     = $this->getRemainingFactor($period);
        $predictedTotalUsage = 0;
        if($remainingFactor != 0){
            $predictedTotalUsage = $predictedUsage / $remainingFactor;
        }

        return [
            'predicted_usage'  => round($predictedUsage, 2),
            'predicted_total'  => round($predictedTotalUsage, 2),
            'trend_percentage' => round($trend * 100, 1),
            'trend_direction'  => $trend >= 0 ? 'up' : 'down',
            'confidence'       => $this->calculateConfidence($historicalData),
        ];
    }

    /**
     * Genereer gepersonaliseerde besparingstips op basis van verbruikspatronen.
     *
     * @param array $electricityData Elektriciteitverbruik data per tijdseenheid
     * @param array $gasData Gasverbruik data per tijdseenheid
     * @param string $period 'day', 'month', of 'year'
     * @param string $housingType Type woning
     * @return array Array met gepersonaliseerde besparingstips
     */
    public function generateSavingTips(array $electricityData, array $gasData, string $period, string $housingType): array
    {
        $tips = [];

        // Analyseer piekverbruik voor elektriciteit
        $electricityPeakTimes = $this->analyzePeakTimes($electricityData, $period);
        if (! empty($electricityPeakTimes)) {
            $tips[] = [
                'type'             => 'electricity',
                'title'            => 'Verlaag je verbruik tijdens piekuren',
                'description'      => "Je verbruikt het meeste elektriciteit tussen {$electricityPeakTimes['start']} en {$electricityPeakTimes['end']}. Verplaats grote apparaten naar daluren om te besparen.",
                'saving_potential' => $electricityPeakTimes['potential'],
            ];
        }

        // Voeg seizoensgebonden tips toe
        $currentMonth = date('n');
        if ($currentMonth >= 10 || $currentMonth <= 3) {
            // Winter tips
            $tips[] = [
                'type'             => 'gas',
                'title'            => 'Optimaliseer je verwarming',
                'description'      => "Een verlaging van 1°C op je thermostaat kan tot 6% besparing op je gasverbruik opleveren.",
                'saving_potential' => '6%',
            ];
        } elseif ($currentMonth >= 4 && $currentMonth <= 9) {
            // Zomer tips
            $tips[] = [
                'type'             => 'electricity',
                'title'            => 'Verminder koelingskosten',
                'description'      => "Gebruik zonwering overdag en ventileer 's nachts om kosten voor airconditioning te besparen.",
                'saving_potential' => '12%',
            ];
        }

        // Woningtype-specifieke tips
        switch ($housingType) {
            case 'appartement':
                $tips[] = [
                    'type'             => 'general',
                    'title'            => 'Appartement isolatie',
                    'description'      => "Appartementbewoners kunnen gemiddeld 8% gas besparen door het isoleren van aangrenzende muren.",
                    'saving_potential' => '8%',
                ];
                break;
            case 'tussenwoning':
                $tips[] = [
                    'type'             => 'general',
                    'title'            => 'Tussenwoningisolatie',
                    'description'      => "Overweeg het isoleren van je dak, dit kan tot 15% besparing op je gasverbruik opleveren in een tussenwoning.",
                    'saving_potential' => '15%',
                ];
                break;
            case 'hoekwoning':
            case 'twee_onder_een_kap':
            case 'vrijstaand':
                $tips[] = [
                    'type'             => 'general',
                    'title'            => 'Wand- en vloerisolatie',
                    'description'      => "Woningen met meerdere buitenmuren kunnen tot 20% besparen door goede muur- en vloerisolatie.",
                    'saving_potential' => '20%',
                ];
                break;
        }

        return $tips;
    }

    /**
     * Bereken trend op basis van een array van datapunten.
     *
     * @param array $data Array van datapunten
     * @return float Trend als een fractie (bijv. 0.05 voor 5% groei)
     */
    private function calculateTrend(array $data): float
    {
        if (count($data) < 2) {
            return 0;
        }

        $first = reset($data);
        $last  = end($data);

        if ($first == 0) {
            return 0;
        }

        return ($last - $first) / $first;
    }

    /**
     * Bereken het gemiddelde van een array van waarden.
     *
     * @param array $data Array van datapunten
     * @return float Gemiddelde waarde
     */
    private function calculateAverage(array $data): float
    {
        if (empty($data)) {
            return 0;
        }

        return array_sum($data) / count($data);
    }

    /**
     * Bepaal seizoensfactor op basis van periode en energietype.
     *
     * @param string $period 'day', 'month', of 'year'
     * @param string $type 'electricity' of 'gas'
     * @return float Seizoensfactor
     */
    private function getSeasonalFactor(string $period, string $type): float
    {
        $month = date('n');

        if ($type === 'gas') {
            // Gas heeft sterke seizoensinvloed
            $winterMonths = [1, 2, 3, 11, 12];
            $summerMonths = [6, 7, 8];

            if (in_array($month, $winterMonths)) {
                return 1.5; // Hoger verbruik in winter
            } elseif (in_array($month, $summerMonths)) {
                return 0.5; // Lager verbruik in zomer
            } else {
                return 1.0; // Gemiddeld verbruik in lente/herfst
            }
        } else {
            // Elektriciteit heeft minder seizoensinvloed
            $winterMonths = [11, 12, 1, 2];
            $summerMonths = [6, 7, 8];

            if (in_array($month, $winterMonths)) {
                return 1.2; // Iets hoger verbruik in winter (verlichting)
            } elseif (in_array($month, $summerMonths)) {
                return 1.1; // Iets hoger verbruik in zomer (koeling)
            } else {
                return 1.0; // Gemiddeld verbruik in lente/herfst
            }
        }
    }

    /**
     * Bepaal welk deel van de periode al verstreken is.
     *
     * @param string $period 'day', 'month', of 'year'
     * @return float Fractie van de periode die al verstreken is
     */
    private function getRemainingFactor(string $period): float
    {
        switch ($period) {
            case 'day':
                $hour = date('G');
                return $hour / 24;
            case 'month':
                $day         = date('j');
                $daysInMonth = date('t');
                return $day / $daysInMonth;
            case 'year':
                $dayOfYear  = date('z');
                $daysInYear = date('L') ? 366 : 365;
                return $dayOfYear / $daysInYear;
            default:
                return 0.5;
        }
    }

    /**
     * Bereken een betrouwbaarheidspercentage voor de voorspelling.
     *
     * @param array $data Array van historische datapunten
     * @return int Betrouwbaarheidspercentage (0-100)
     */
    private function calculateConfidence(array $data): int
    {
        // Eenvoudige implementatie: hoe meer datapunten, hoe hoger de betrouwbaarheid (max 90%)
        $baseConfidence = 50;
        $dataBonus      = min(count($data) * 5, 40);

        return $baseConfidence + $dataBonus;
    }

    /**
     * Analyseer piektijden in het verbruik.
     *
     * @param array $data Array van verbruiksgegevens
     * @param string $period 'day', 'month', of 'year'
     * @return array Informatie over piektijden
     */
    private function analyzePeakTimes(array $data, string $period): array
    {
        if (empty($data)) {
            return [];
        }

        // Veronderstel dat de data-array is geïndexeerd van 0 tot 23 voor uren van de dag
        if ($period === 'day') {
            // Zoek het piekuur
            $maxValue = max($data);
            $peakHour = array_search($maxValue, $data);

            if ($peakHour !== false) {
                $startHour = $peakHour;
                $endHour   = $peakHour;

                // Zoek aangrenzende uren met hoog verbruik
                while (isset($data[$startHour - 1]) && $data[$startHour - 1] > $maxValue * 0.8) {
                    $startHour--;
                }

                while (isset($data[$endHour + 1]) && $data[$endHour + 1] > $maxValue * 0.8) {
                    $endHour++;
                }

                // Bereken besparingspotentieel
                $peakUsage = 0;
                for ($i = $startHour; $i <= $endHour; $i++) {
                    $peakUsage += isset($data[$i]) ? $data[$i] : 0;
                }

                $totalUsage      = array_sum($data);
                $peakPercentage  = ($totalUsage > 0) ? ($peakUsage / $totalUsage) * 100 : 0;
                $potentialSaving = round($peakPercentage * 0.3); // 30% van piekverbruik kan worden bespaard

                return [
                    'start'      => sprintf('%02d:00', $startHour),
                    'end'        => sprintf('%02d:00', $endHour + 1),
                    'percentage' => round($peakPercentage),
                    'potential'  => "{$potentialSaving}%",
                ];
            }
        }

        return [];
    }
}
