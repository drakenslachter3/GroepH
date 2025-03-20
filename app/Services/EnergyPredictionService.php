<?php

namespace App\Services;

class EnergyPredictionService
{
    /**
     * Predict electricity usage based on current usage pattern and historical trends.
     *
     * @param array $currentUsage Array of electricity usage values
     * @param string $period 'day', 'month', or 'year'
     * @return array Predicted values
     */
    public function predictElectricityUsage(array $currentUsage, string $period): array
    {
        return $this->generatePrediction($currentUsage, $period, 'electricity');
    }
    
    /**
     * Predict gas usage based on current usage pattern and historical trends.
     *
     * @param array $currentUsage Array of gas usage values
     * @param string $period 'day', 'month', or 'year'
     * @return array Predicted values
     */
    public function predictGasUsage(array $currentUsage, string $period): array
    {
        return $this->generatePrediction($currentUsage, $period, 'gas');
    }
    
    /**
     * Generate prediction based on usage pattern, historical trends, and energy type.
     *
     * @param array $currentUsage Array of current usage values
     * @param string $period 'day', 'month', or 'year'
     * @param string $energyType 'electricity' or 'gas'
     * @return array Predicted values
     */
    private function generatePrediction(array $currentUsage, string $period, string $energyType): array
    {
        // Empty input check
        if (empty($currentUsage)) {
            return [];
        }
        
        // Calculate average and trend from current usage
        $count = count($currentUsage);
        $avgUsage = array_sum($currentUsage) / $count;
        
        // Calculate trend based on first half vs second half
        $halfPoint = floor($count / 2);
        $firstHalfAvg = array_sum(array_slice($currentUsage, 0, $halfPoint)) / max(1, $halfPoint);
        $secondHalfAvg = array_sum(array_slice($currentUsage, $halfPoint)) / max(1, $count - $halfPoint);
        
        // Calculate trend factor (percentage change)
        $trendFactor = $firstHalfAvg > 0 ? ($secondHalfAvg - $firstHalfAvg) / $firstHalfAvg : 0;
        
        // Different prediction strategies based on period
        switch ($period) {
            case 'day':
                return $this->predictDayUsage($currentUsage, $trendFactor, $energyType);
                
            case 'month':
                return $this->predictMonthUsage($currentUsage, $trendFactor, $energyType);
                
            case 'year':
                return $this->predictYearUsage($currentUsage, $trendFactor, $energyType);
                
            default:
                // Default basic prediction
                $prediction = [];
                for ($i = 0; $i < $count; $i++) {
                    $prediction[] = round($avgUsage * (1 + $trendFactor), 2);
                }
                return $prediction;
        }
    }
    
    /**
     * Predict day usage (hourly) based on current pattern and time of day.
     */
    private function predictDayUsage(array $currentUsage, float $trendFactor, string $energyType): array
    {
        $count = count($currentUsage);
        $lastHours = array_slice($currentUsage, -3); // Last 3 hours
        $lastHoursAvg = array_sum($lastHours) / count($lastHours);
        
        $prediction = [];
        $currentHour = intval(date('G')); // 0-23
        
        // Hourly patterns - indexed 0-23
        $hourlyFactors = $energyType === 'electricity' 
            ? $this->getHourlyElectricityFactors() 
            : $this->getHourlyGasFactors();
        
        // Predict next hours based on hourly patterns
        for ($i = 1; $i <= 24; $i++) {
            $nextHour = ($currentHour + $i) % 24;
            $currentHourFactor = $hourlyFactors[$currentHour] ?: 1;
            $nextHourFactor = $hourlyFactors[$nextHour] ?: 1;
            
            // Calculate relative change between current hour and next hour based on typical pattern
            $relativeChange = $currentHourFactor > 0 ? $nextHourFactor / $currentHourFactor : 1;
            
            // Apply the pattern change to the last measured average
            $predictedValue = $lastHoursAvg * $relativeChange;
            
            // Apply trend factor with diminishing effect over time
            $trendEffect = $trendFactor * (1 - ($i / 48)); // Trend effect diminishes
            $predictedValue *= (1 + $trendEffect);
            
            // Add small random variation
            $randomFactor = 0.95 + (mt_rand(0, 10) / 100);
            $predictedValue *= $randomFactor;
            
            $prediction[] = round($predictedValue, $energyType === 'electricity' ? 2 : 3);
        }
        
        return $prediction;
    }
    
    /**
     * Predict month usage (daily) based on current pattern, weekday/weekend patterns.
     */
    private function predictMonthUsage(array $currentUsage, float $trendFactor, string $energyType): array
    {
        $count = count($currentUsage);
        $lastDays = array_slice($currentUsage, -5); // Last 5 days
        $lastDaysAvg = array_sum($lastDays) / count($lastDays);
        
        $prediction = [];
        $currentDayOfWeek = intval(date('N')); // 1-7 (Monday-Sunday)
        $daysInMonth = intval(date('t'));
        $currentDay = intval(date('j'));
        
        // Weekend vs weekday factors
        $weekdayFactor = 1.0;
        $weekendFactor = $energyType === 'electricity' ? 1.25 : 1.15;
        
        // Seasonal adjustment based on month
        $currentMonth = intval(date('n'));
        $seasonalFactor = $energyType === 'electricity' 
            ? $this->getMonthlyElectricityFactors()[$currentMonth - 1] 
            : $this->getMonthlyGasFactors()[$currentMonth - 1];
        
        // Predict remaining days in month
        for ($i = $currentDay + 1; $i <= $daysInMonth; $i++) {
            $nextDayOfWeek = ($currentDayOfWeek + ($i - $currentDay)) % 7;
            $isWeekend = ($nextDayOfWeek == 0 || $nextDayOfWeek == 6);
            
            // Apply weekday/weekend factor
            $dayFactor = $isWeekend ? $weekendFactor : $weekdayFactor;
            
            // Calculate predicted value
            $predictedValue = $lastDaysAvg * $dayFactor * $seasonalFactor;
            
            // Apply trend with diminishing effect
            $daysAhead = $i - $currentDay;
            $trendEffect = $trendFactor * (1 - ($daysAhead / 60)); // Trend diminishes over time
            $predictedValue *= (1 + $trendEffect);
            
            // Add random variation
            $randomFactor = 0.95 + (mt_rand(0, 15) / 100);
            $predictedValue *= $randomFactor;
            
            $prediction[] = round($predictedValue, $energyType === 'electricity' ? 2 : 2);
        }
        
        return $prediction;
    }
    
    /**
     * Predict yearly usage (monthly) based on seasonal patterns.
     */
    private function predictYearUsage(array $currentUsage, float $trendFactor, string $energyType): array
    {
        $count = count($currentUsage);
        $lastMonthValue = end($currentUsage);
        $currentMonth = intval(date('n'));
        
        $prediction = [];
        
        // Get seasonal factors
        $monthlyFactors = $energyType === 'electricity' 
            ? $this->getMonthlyElectricityFactors() 
            : $this->getMonthlyGasFactors();
        
        // Current month's factor for baseline
        $currentMonthFactor = $monthlyFactors[$currentMonth - 1];
        
        // Predict remaining months in the year
        for ($i = $currentMonth + 1; $i <= 12; $i++) {
            // Relative change from current month to next month based on seasonal pattern
            $nextMonthFactor = $monthlyFactors[$i - 1];
            $relativeChange = $currentMonthFactor > 0 ? $nextMonthFactor / $currentMonthFactor : 1;
            
            // Calculate predicted value
            $predictedValue = $lastMonthValue * $relativeChange;
            
            // Apply trend with diminishing effect
            $monthsAhead = $i - $currentMonth;
            $trendEffect = $trendFactor * (1 - ($monthsAhead / 24)); // Diminishing trend effect
            $predictedValue *= (1 + $trendEffect);
            
            // Add random variation (less for longer-term predictions)
            $randomFactor = 0.97 + (mt_rand(0, 6) / 100);
            $predictedValue *= $randomFactor;
            
            $prediction[] = round($predictedValue, $energyType === 'electricity' ? 1 : 1);
        }
        
        return $prediction;
    }
    
    /**
     * Generate personalized energy saving tips based on usage patterns.
     */
    public function generateSavingTips(array $electricityUsage, array $gasUsage, string $period, string $housingType): array
    {
        // Determine the usage pattern from the electricity data
        $usagePattern = $this->analyzeUsagePattern($electricityUsage, $period);
        
        // Determine the current season
        $currentMonth = intval(date('n'));
        $season = $this->determineSeason($currentMonth);
        
        // Generate housing type specific tips
        $housingTypeTips = $this->getHousingTypeTips($housingType);
        
        // Generate season specific tips
        $seasonTips = $this->getSeasonTips($season, $housingType);
        
        // Generate electricity specific tips based on usage pattern
        $electricityTips = $this->getElectricityTips($usagePattern);
        
        return [
            'pattern' => $usagePattern,
            'season' => $season,
            'housing' => $housingTypeTips,
            'electricity' => $electricityTips,
            'seasonal' => $seasonTips,
        ];
    }
    
    /**
     * Analyze usage pattern to determine when peak usage occurs.
     */
    private function analyzeUsagePattern(array $electricityUsage, string $period): string
    {
        if ($period === 'day') {
            // For daily view, determine if usage peaks in morning, afternoon, or evening
            $morning = array_sum(array_slice($electricityUsage, 6, 6)); // 6AM-12PM
            $afternoon = array_sum(array_slice($electricityUsage, 12, 6)); // 12PM-6PM
            $evening = array_sum(array_slice($electricityUsage, 18, 6)); // 6PM-12AM
            
            if ($morning > $afternoon && $morning > $evening) {
                return 'ochtend';
            } elseif ($evening > $morning && $evening > $afternoon) {
                return 'avond';
            } else {
                return 'middag';
            }
        } elseif ($period === 'month') {
            // For monthly view, determine weekday vs weekend usage
            $weekdaySum = 0;
            $weekdayCount = 0;
            $weekendSum = 0;
            $weekendCount = 0;
            
            for ($i = 0; $i < count($electricityUsage); $i++) {
                // Determine if this index is a weekday or weekend
                // This is an approximation as we don't have the actual date
                if ($i % 7 >= 5) { // Assuming data starts on a Monday
                    $weekendSum += $electricityUsage[$i];
                    $weekendCount++;
                } else {
                    $weekdaySum += $electricityUsage[$i];
                    $weekdayCount++;
                }
            }
            
            $weekdayAvg = $weekdayCount > 0 ? $weekdaySum / $weekdayCount : 0;
            $weekendAvg = $weekendCount > 0 ? $weekendSum / $weekendCount : 0;
            
            if ($weekendAvg > $weekdayAvg * 1.2) {
                return 'weekend';
            } else {
                return 'doordeweeks';
            }
        }
        
        // Default
        return 'gemiddeld';
    }
    
    /**
     * Determine current season based on month.
     */
    private function determineSeason(int $month): string
    {
        if ($month >= 3 && $month <= 5) {
            return 'lente';
        } elseif ($month >= 6 && $month <= 8) {
            return 'zomer';
        } elseif ($month >= 9 && $month <= 11) {
            return 'herfst';
        } else {
            return 'winter';
        }
    }
    
    /**
     * Get hourly electricity usage factors (0-23 hours).
     */
    private function getHourlyElectricityFactors(): array
    {
        return [
            0.3, 0.2, 0.15, 0.15, 0.15, 0.25,  // 0-5u: low nighttime usage
            0.6, 1.3, 1.8, 1.1, 0.8, 0.9,      // 6-11u: morning peak
            0.7, 0.8, 0.7, 0.7, 0.9, 1.4,      // 12-17u: midday usage
            2.3, 2.5, 2.0, 1.4, 0.9, 0.5       // 18-23u: evening peak
        ];
    }
    
    /**
     * Get hourly gas usage factors (0-23 hours).
     */
    private function getHourlyGasFactors(): array
    {
        return [
            0.2, 0.15, 0.15, 0.15, 0.15, 0.2,  // 0-5u: minimal night usage
            1.2, 2.1, 1.2, 0.7, 0.5, 0.5,      // 6-11u: morning peak (heating, shower)
            0.6, 0.5, 0.4, 0.4, 0.6, 1.1,      // 12-17u: midday usage 
            2.3, 2.1, 1.4, 1.0, 0.6, 0.4       // 18-23u: evening peak (heating, cooking)
        ];
    }
    
    /**
     * Get monthly electricity distribution factors.
     */
    private function getMonthlyElectricityFactors(): array
    {
        return [
            1.18, 1.10, 1.00, 0.92, 0.85, 0.80, 0.85, 0.90, 0.95, 1.05, 1.15, 1.25
        ];
    }
    
    /**
     * Get monthly gas distribution factors.
     */
    private function getMonthlyGasFactors(): array
    {
        return [
            2.30, 2.10, 1.50, 1.00, 0.50, 0.30, 0.25, 0.25, 0.45, 0.95, 1.70, 2.20
        ];
    }
    
    /**
     * Get housing type specific saving tips.
     */
    private function getHousingTypeTips(string $housingType): string
    {
        switch ($housingType) {
            case 'appartement':
                return 'In een appartement kunt u tot 15% op gasverbruik besparen door radiatorfolie te plaatsen achter radiatoren aan de buitenmuur en door tochtstrips rond ramen en deuren aan te brengen.';
            
            case 'tussenwoning':
                return 'Voor een tussenwoning is dakisolatie zeer effectief. Hiermee bespaart u tot 20% op uw gasverbruik. Ook het isoleren van de vloer levert een besparing van 10% op.';
            
            case 'hoekwoning':
                return 'Bij een hoekwoning kan spouwmuurisolatie tot 25% besparing op uw gasrekening opleveren. Ook het isoleren van de vloer bespaart ongeveer 10%.';
            
            case 'twee_onder_een_kap':
                return 'Voor een twee-onder-een-kapwoning is een combinatie van spouwmuurisolatie en dakisolatie het meest effectief, met een potentiële besparing tot 30%.';
            
            case 'vrijstaand':
                return 'Bij een vrijstaande woning kunt u het meest besparen met complete schilisolatie (muren, dak en vloer), wat tot 35% besparing op uw gasverbruik kan opleveren.';
            
            default:
                return 'Isolatie is de effectiefste manier om gasverbruik te verlagen. Begin met de grootste oppervlakken zoals het dak en de muren voor het beste resultaat.';
        }
    }
    
    /**
     * Get electricity tips based on usage pattern.
     */
    private function getElectricityTips(string $usagePattern): string
    {
        switch ($usagePattern) {
            case 'ochtend':
                return 'Uw elektriciteitsverbruik piekt in de ochtenduren. Spreid het gebruik van grote apparaten zoals wasmachine en vaatwasser over de dag om pieken te vermijden en overweeg timers te gebruiken om apparaten buiten piektijden te laten draaien.';
            
            case 'middag':
                return 'Uw elektriciteitsverbruik is het hoogst midden op de dag. Als u zonnepanelen overweegt, zou dit patroon goed aansluiten bij de zonne-energieproductie, vooral in de zomer.';
            
            case 'avond':
                return 'Uw elektriciteitsverbruik is het hoogst tussen 18:00 en 21:00 uur. Overweeg het gebruik van grote apparaten zoals wasmachine en vaatwasser te verplaatsen naar daluren (na 22:00) om ongeveer 14% te besparen op uw elektriciteitskosten.';
            
            case 'weekend':
                return 'U verbruikt beduidend meer elektriciteit in het weekend. Overweeg energiezuinige activiteiten en maak optimaal gebruik van daglicht voor hobby\'s en huishoudelijke taken.';
            
            case 'doordeweeks':
                return 'Uw elektriciteitsverbruik is doordeweeks het hoogst. Controleer sluipverbruik van apparaten die aan staan terwijl u niet thuis bent, zoals computers, modems en stand-by apparaten.';
            
            default:
                return 'Door LED-verlichting te gebruiken in plaats van gloeilampen kunt u tot 80% energie besparen op uw verlichting. Ook het uitschakelen van apparaten in plaats van stand-by bespaart jaarlijks tot €70.';
        }
    }
    
    /**
     * Get season specific tips.
     */
    private function getSeasonTips(string $season, string $housingType): string
    {
        switch ($season) {
            case 'winter':
                $tips = 'In de winter bespaart u gas door de thermostaat \'s nachts en bij afwezigheid op 15°C te zetten. Elke graad lager kan tot 6% besparing opleveren.';
                
                if ($housingType === 'vrijstaand' || $housingType === 'twee_onder_een_kap' || $housingType === 'hoekwoning') {
                    $tips .= ' Let extra op tocht via ramen en deuren bij vrijstaande of hoekwoningen.';
                }
                
                return $tips;
                
            case 'lente':
                return 'In de lente kunt u de verwarming vaak al uit zetten en natuurlijke ventilatie gebruiken. Zet ramen tegenover elkaar open voor effectieve doorluchting zonder energieverlies.';
                
            case 'zomer':
                $tips = 'In de zomer kunt u uw energierekening laag houden door zonwering te gebruiken overdag en \'s nachts te ventileren in plaats van airconditioning.';
                
                if ($housingType === 'appartement') {
                    $tips .= ' In appartementen op hogere verdiepingen is hitte een uitdaging, overweeg reflecterende raamfolie.';
                }
                
                return $tips;
                
            case 'herfst':
                return 'In de herfst is het verstandig om uw cv-installatie te laten onderhouden voor optimale efficiëntie. Ontlucht radiatoren en test uw thermostaat voor het stookseizoen begint.';
                
            default:
                return 'Pas uw energiegebruik aan op het seizoen. In de zomer: focus op koeling zonder airco. In de winter: isolatie en efficiënt verwarmen zijn het belangrijkst.';
        }
    }
}