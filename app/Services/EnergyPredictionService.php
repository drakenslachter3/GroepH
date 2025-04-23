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
        return $this->predictEnergyUsage($historicalData, $period, 'electricity');
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
        return $this->predictEnergyUsage($historicalData, $period, 'gas');
    }
    
    /**
     * Enhanced prediction method that provides detailed forecasting with best and worst case scenarios
     * 
     * @param array $historicalData Array of historical usage data
     * @param string $period 'day', 'month', or 'year'
     * @param string $type 'electricity' or 'gas'
     * @return array Predicted usage with scenarios and trend lines
     */
    private function predictEnergyUsage(array $historicalData, string $period, string $type): array
    {
        // Calculate basic trend and average as before
        $recentData = array_slice($historicalData, -3);
        $trend = $this->calculateTrend($recentData);
        $averageUsage = $this->calculateAverage($historicalData);
        $seasonalFactor = $this->getSeasonalFactor($period, $type);
        
        // Get the period length for the projection
        $periodLength = $this->getPeriodLength($period);
        
        // Calculate the base prediction
        $predictedUsage = $averageUsage * (1 + $trend) * $seasonalFactor;
        $remainingFactor = $this->getRemainingFactor($period);
        $predictedTotalUsage = $predictedUsage / $remainingFactor;
        
        // Calculate confidence based on data quality and volatility
        $confidence = $this->calculateConfidence($historicalData);
        
        // Calculate margin based on confidence (lower confidence = wider margin)
        $marginPercentage = (100 - $confidence) / 100 * 0.5; // 0-50% margin based on confidence
        
        // Calculate best and worst case scenarios
        $bestCaseUsage = $predictedTotalUsage * (1 - $marginPercentage);
        $worstCaseUsage = $predictedTotalUsage * (1 + $marginPercentage);
        
        // Generate trend lines for visualization
        $actualData = $this->generateActualData($historicalData, $period);
        $predictionLine = $this->generatePredictionLine($actualData, $predictedTotalUsage, $period);
        $bestCaseLine = $this->generatePredictionLine($actualData, $bestCaseUsage, $period);
        $worstCaseLine = $this->generatePredictionLine($actualData, $worstCaseUsage, $period);
        
        // Enhanced return data with all required information for visualization
        return [
            'predicted_usage' => round($predictedUsage, 2),
            'predicted_total' => round($predictedTotalUsage, 2),
            'best_case' => round($bestCaseUsage, 2),
            'worst_case' => round($worstCaseUsage, 2),
            'confidence' => $confidence,
            'trend_percentage' => round($trend * 100, 1),
            'trend_direction' => $trend >= 0 ? 'up' : 'down',
            'margin' => round($marginPercentage * 100, 1),
            'actual' => $actualData,
            'prediction' => $predictionLine,
            'best_case_line' => $bestCaseLine,
            'worst_case_line' => $worstCaseLine,
            'expected' => round($predictedTotalUsage, 2)
        ];
    }
    
    /**
     * Generate actual data points based on historical data and period type
     * 
     * @param array $historicalData Historical usage data
     * @param string $period Period type ('day', 'month', 'year')
     * @return array Array of actual data points for the chart
     */
    private function generateActualData(array $historicalData, string $period): array
    {
        $periodLength = $this->getPeriodLength($period);
        $actualData = [];
        
        // Use historical data where available, zero otherwise
        for ($i = 0; $i < $periodLength; $i++) {
            $actualData[$i] = isset($historicalData[$i]) ? $historicalData[$i] : 0;
        }
        
        // Determine current position in period
        $currentPoint = $this->getCurrentPositionInPeriod($period);
        
        // Zero out future data points (we don't have actual data for the future)
        for ($i = $currentPoint + 1; $i < $periodLength; $i++) {
            $actualData[$i] = null;
        }
        
        return $actualData;
    }
    
    /**
     * Generate prediction line for chart visualization
     * 
     * @param array $actualData Actual usage data
     * @param float $predictedTotal Predicted total usage
     * @param string $period Period type
     * @return array Prediction line values
     */
    private function generatePredictionLine(array $actualData, float $predictedTotal, string $period): array
    {
        $periodLength = $this->getPeriodLength($period);
        $currentPoint = $this->getCurrentPositionInPeriod($period);
        $predictionLine = array_fill(0, $periodLength, null);
        
        // Calculate the sum of actual data up to current point
        $actualSum = 0;
        for ($i = 0; $i <= $currentPoint; $i++) {
            $actualSum += $actualData[$i] ?? 0;
        }
        
        // Place the actual sum at the current point
        $predictionLine[$currentPoint] = $actualSum;
        
        // Calculate remaining usage to be distributed over future periods
        $remainingUsage = $predictedTotal - $actualSum;
        $remainingPeriods = $periodLength - $currentPoint - 1;
        
        if ($remainingPeriods > 0) {
            // Apply seasonal factors to distribute the remaining usage
            $seasonalFactors = $this->getSeasonalDistributionFactors($period, $currentPoint + 1, $periodLength);
            $factorSum = array_sum($seasonalFactors);
            
            // Distribute the remaining usage according to seasonal factors
            for ($i = $currentPoint + 1; $i < $periodLength; $i++) {
                $factor = $seasonalFactors[$i - $currentPoint - 1] / $factorSum;
                $predictionValue = $actualSum + ($remainingUsage * $factor * ($i - $currentPoint) / $remainingPeriods);
                $predictionLine[$i] = $predictionValue;
            }
        }
        
        return $predictionLine;
    }
    
    /**
     * Get seasonal distribution factors for the remaining period
     * 
     * @param string $period Period type
     * @param int $startPoint Starting point in the period
     * @param int $endPoint Ending point in the period
     * @return array Seasonal factors for each remaining point
     */
    private function getSeasonalDistributionFactors(string $period, int $startPoint, int $endPoint): array
    {
        $factors = [];
        
        switch ($period) {
            case 'day':
                // Hourly factors - higher in morning and evening
                $hourlyFactors = [
                    0.6, 0.4, 0.3, 0.2, 0.3, 0.5, // 0-5 night
                    0.8, 1.2, 1.5, 1.3, 1.0, 0.9, // 6-11 morning
                    1.0, 1.1, 1.0, 1.1, 1.2, 1.4, // 12-17 afternoon
                    1.8, 1.7, 1.5, 1.2, 1.0, 0.7  // 18-23 evening
                ];
                
                for ($i = $startPoint; $i < $endPoint; $i++) {
                    $factors[] = $hourlyFactors[$i % 24];
                }
                break;
                
            case 'month':
                // Daily factors - slightly higher on weekends
                for ($i = $startPoint; $i < $endPoint; $i++) {
                    // Calculate day of week (0 = Sunday, 6 = Saturday)
                    $dayOfWeek = date('w', strtotime(date('Y-m') . '-' . ($i + 1)));
                    $factors[] = ($dayOfWeek == 0 || $dayOfWeek == 6) ? 1.2 : 1.0;
                }
                break;
                
            case 'year':
            default:
                // Monthly factors - seasonal variations
                $monthlyFactors = [
                    1.2, 1.1, 1.0, 0.9, 0.8, 0.7, // Jan-Jun
                    0.7, 0.8, 0.9, 1.0, 1.1, 1.2  // Jul-Dec
                ];
                
                for ($i = $startPoint; $i < $endPoint; $i++) {
                    $factors[] = $monthlyFactors[$i % 12];
                }
                break;
        }
        
        return $factors;
    }
    
    /**
     * Get the length of the period in data points
     * 
     * @param string $period Period type
     * @return int Number of data points in the period
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
     * Get the current position in the period
     * 
     * @param string $period Period type
     * @return int Current position (0-based index)
     */
    private function getCurrentPositionInPeriod(string $period): int
    {
        switch ($period) {
            case 'day':
                return (int)date('G'); // Current hour (0-23)
            case 'month':
                return (int)date('j') - 1; // Current day (0-30)
            case 'year':
            default:
                return (int)date('n') - 1; // Current month (0-11)
        }
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
        $last = end($data);
        
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
        switch($period) {
            case 'day':
                $hour = date('G');
                return $hour / 24;
            case 'month':
                $day = date('j');
                $daysInMonth = date('t');
                return $day / $daysInMonth;
            case 'year':
                $dayOfYear = date('z');
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
        // Base confidence starts at 50%
        $baseConfidence = 50;
        
        // More data points increase confidence (up to +20%)
        $dataPointsBonus = min(count($data) * 2, 20);
        
        // Consistent data increases confidence (up to +20%)
        $volatility = $this->calculateVolatility($data);
        $consistencyBonus = $volatility < 0.5 ? 20 : ($volatility < 1 ? 10 : 0);
        
        // Recent data increases confidence (up to +10%)
        $recencyBonus = count($data) > 0 && end($data) !== false ? 10 : 0;
        
        // Final confidence score
        return min($baseConfidence + $dataPointsBonus + $consistencyBonus + $recencyBonus, 95);
    }
    
    /**
     * Calculate the volatility of data (coefficient of variation)
     * 
     * @param array $data Data points
     * @return float Volatility measure
     */
    private function calculateVolatility(array $data): float
    {
        if (count($data) < 2) {
            return 1.0; // High volatility by default for insufficient data
        }
        
        $mean = $this->calculateAverage($data);
        
        if ($mean == 0) {
            return 1.0; // Avoid division by zero
        }
        
        // Calculate standard deviation
        $sumSquaredDifferences = 0;
        foreach ($data as $value) {
            $difference = $value - $mean;
            $sumSquaredDifferences += $difference * $difference;
        }
        
        $variance = $sumSquaredDifferences / count($data);
        $standardDeviation = sqrt($variance);
        
        // Coefficient of variation (normalized measure of dispersion)
        return $standardDeviation / $mean;
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
        if (!empty($electricityPeakTimes)) {
            $tips[] = [
                'type' => 'electricity',
                'title' => 'Verlaag je verbruik tijdens piekuren',
                'description' => "Je verbruikt het meeste elektriciteit tussen {$electricityPeakTimes['start']} en {$electricityPeakTimes['end']}. Verplaats grote apparaten naar daluren om te besparen.",
                'saving_potential' => $electricityPeakTimes['potential']
            ];
        }
        
        // Voeg seizoensgebonden tips toe
        $currentMonth = date('n');
        if ($currentMonth >= 10 || $currentMonth <= 3) {
            // Winter tips
            $tips[] = [
                'type' => 'gas',
                'title' => 'Optimaliseer je verwarming',
                'description' => "Een verlaging van 1°C op je thermostaat kan tot 6% besparing op je gasverbruik opleveren.",
                'saving_potential' => '6%'
            ];
        } elseif ($currentMonth >= 4 && $currentMonth <= 9) {
            // Zomer tips
            $tips[] = [
                'type' => 'electricity',
                'title' => 'Verminder koelingskosten',
                'description' => "Gebruik zonwering overdag en ventileer 's nachts om kosten voor airconditioning te besparen.",
                'saving_potential' => '12%'
            ];
        }
        
        // Woningtype-specifieke tips
        switch($housingType) {
            case 'appartement':
                $tips[] = [
                    'type' => 'general',
                    'title' => 'Appartement isolatie',
                    'description' => "Appartementbewoners kunnen gemiddeld 8% gas besparen door het isoleren van aangrenzende muren.",
                    'saving_potential' => '8%'
                ];
                break;
            case 'tussenwoning':
                $tips[] = [
                    'type' => 'general',
                    'title' => 'Tussenwoningisolatie',
                    'description' => "Overweeg het isoleren van je dak, dit kan tot 15% besparing op je gasverbruik opleveren in een tussenwoning.",
                    'saving_potential' => '15%'
                ];
                break;
            case 'hoekwoning':
            case 'twee_onder_een_kap':
            case 'vrijstaand':
                $tips[] = [
                    'type' => 'general',
                    'title' => 'Wand- en vloerisolatie',
                    'description' => "Woningen met meerdere buitenmuren kunnen tot 20% besparen door goede muur- en vloerisolatie.",
                    'saving_potential' => '20%'
                ];
                break;
        }
        
        return $tips;
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
                $endHour = $peakHour;
                
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
                
                $totalUsage = array_sum($data);
                $peakPercentage = ($totalUsage > 0) ? ($peakUsage / $totalUsage) * 100 : 0;
                $potentialSaving = round($peakPercentage * 0.3); // 30% van piekverbruik kan worden bespaard
                
                return [
                    'start' => sprintf('%02d:00', $startHour),
                    'end' => sprintf('%02d:00', $endHour + 1),
                    'percentage' => round($peakPercentage),
                    'potential' => "{$potentialSaving}%"
                ];
            }
        }
        
        return [];
    }
}