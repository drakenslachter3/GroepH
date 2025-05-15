<?php

namespace App\Services;

/**
 * Class EnergyPredictionLineGenerator
 * Specialized class for generating prediction lines with consistent scenario handling
 * 
 * @package App\Services
 */
class EnergyPredictionLineGenerator
{
    /**
     * Generate a prediction line for a specific scenario
     * 
     * @param array $actualData Actual data points
     * @param float $predictedTotal Total predicted value
     * @param string $period Period type ('day', 'month', 'year')
     * @param string $type Energy type ('electricity', 'gas')
     * @param string $scenario Scenario type ('expected', 'best', 'worst')
     * @return array The generated prediction line
     */
    public function generateLine(
        array $actualData, 
        float $predictedTotal, 
        string $period, 
        string $type, 
        string $scenario = 'expected'
    ): array {
        // Get period dimensions
        $periodLength = $this->getPeriodLength($period);
        $currentPoint = $this->getCurrentPositionInPeriod($period);
        
        // Initialize prediction line with nulls
        $predictionLine = array_fill(0, $periodLength, null);
        
        // Find last valid actual data point
        $lastData = $this->findLastActualDataPoint($actualData, $currentPoint);
        $lastActualValue = $lastData['value'];
        $lastActualIndex = $lastData['index'];
        
        // If we couldn't find any actual data, use estimated value
        if ($lastActualValue === null) {
            $lastActualValue = $this->getEstimatedValue($period, $type);
            $lastActualIndex = max(0, $currentPoint - 1);
        }
        
        // Calculate the sum of actual data up to current point
        $actualSum = $this->calculateActualSum($actualData, $currentPoint);
        
        // Apply scenario-specific scaling to total prediction
        // These factors affect the height of the entire forecast
        $scenarioScaleFactor = $this->getScenarioScaleFactor($scenario);
        $scenarioAdjustedTotal = $predictedTotal * $scenarioScaleFactor;
        
        // Calculate remaining usage to be distributed
        $remainingUsage = max(0, $scenarioAdjustedTotal - $actualSum);
        $remainingPeriods = $periodLength - $currentPoint - 1;
        
        if ($remainingPeriods <= 0) {
            return $predictionLine; // No future periods to predict
        }
        
        // Start with the known actual value - this connects the line to reality
        $predictionLine[$lastActualIndex] = $lastActualValue;
        
        // Choose generation strategy based on period
        if ($period === 'year') {
            return $this->generateYearLine(
                $actualData, 
                $lastActualValue, 
                $lastActualIndex,
                $currentPoint, 
                $periodLength, 
                $type, 
                $scenario
            );
        } else if ($period === 'day') {
            return $this->generateDayLine(
                $predictionLine,
                $lastActualValue,
                $lastActualIndex,
                $currentPoint,
                $periodLength,
                $actualSum,
                $remainingUsage,
                $remainingPeriods,
                $type,
                $scenario
            );
        } else {
            // Month view
            return $this->generateMonthLine(
                $predictionLine,
                $lastActualValue,
                $lastActualIndex,
                $currentPoint,
                $periodLength,
                $actualSum,
                $remainingUsage,
                $type,
                $scenario
            );
        }
    }
    
    /**
     * Generate year view prediction line
     */
    private function generateYearLine(
        array $actualData,
        float $lastActualValue,
        int $lastActualIndex,
        int $currentPoint,
        int $periodLength,
        string $type,
        string $scenario
    ): array {
        $predictionLine = array_fill(0, $periodLength, null);
        
        // Use the last known actual value as the starting point
        $predictionLine[$lastActualIndex] = $lastActualValue;
        
        // Calculate average monthly value from actual data
        $actualValues = array_filter($actualData, function($val) { return $val !== null; });
        $avgMonthlyValue = !empty($actualValues) ? array_sum($actualValues) / count($actualValues) : 
                          ($type === 'electricity' ? 300 : 150); // Default values
        
        // Apply scenario adjustment to the base monthly value
        if ($scenario === 'best') {
            $avgMonthlyValue *= 0.9; // 10% less consumption for best case
        } else if ($scenario === 'worst') {
            $avgMonthlyValue *= 1.15; // 15% more consumption for worst case
        }
        
        // Define seasonal patterns per energy type
        $monthlyPatterns = $type === 'electricity' 
            ? [1.1, 1.0, 0.9, 0.8, 0.9, 1.0, 1.1, 1.1, 1.0, 1.1, 1.2, 1.3]  // Electricity seasonal pattern
            : [1.8, 1.6, 1.4, 1.0, 0.6, 0.4, 0.3, 0.3, 0.5, 0.9, 1.4, 1.8]; // Gas seasonal pattern
        
        // Further adjust patterns by scenario (make difference more visible)
        if ($scenario === 'best') {
            // For best case, flatten the peaks a bit (less seasonal variation)
            $monthlyPatterns = array_map(function($factor) {
                return $factor > 1.0 ? ($factor * 0.8 + 0.2) : ($factor * 0.7 + 0.3);
            }, $monthlyPatterns);
        } else if ($scenario === 'worst') {
            // For worst case, exaggerate the peaks (more seasonal variation)
            $monthlyPatterns = array_map(function($factor) {
                return $factor > 1.0 ? ($factor * 1.1) : ($factor * 0.9);
            }, $monthlyPatterns);
        }
        
        // Generate the prediction line
        $lastValue = $lastActualValue;
        
        for ($i = $lastActualIndex + 1; $i < $periodLength; $i++) {
            // Apply seasonal pattern scaled by monthly average
            $seasonalFactor = $monthlyPatterns[$i % 12];
            $predictedValue = $avgMonthlyValue * $seasonalFactor;
            
            // Ensure smooth transition from last actual value
            if ($i === $currentPoint + 1) {
                // First prediction point should be close to last actual
                $predictedValue = ($lastValue * 0.7) + ($predictedValue * 0.3);
            } else if ($i === $currentPoint + 2) {
                // Second prediction point - smoother transition
                $predictedValue = ($lastValue * 0.3) + ($predictedValue * 0.7);
            }
            
            $predictionLine[$i] = $predictedValue;
            $lastValue = $predictedValue;
        }
        
        // Apply scenario-specific adjustment to curve shape
        if ($scenario === 'best') {
            // Best case has a slight downward trend over time (improvement)
            $this->applyTrendToLine($predictionLine, $currentPoint, -0.05);
        } else if ($scenario === 'worst') {
            // Worst case has a slight upward trend over time (worsening)
            $this->applyTrendToLine($predictionLine, $currentPoint, 0.07);
        }
        
        return $predictionLine;
    }
    
    /**
     * Generate day view prediction line
     */
    private function generateDayLine(
        array $predictionLine,
        float $lastActualValue,
        int $lastActualIndex,
        int $currentPoint,
        int $periodLength,
        float $actualSum,
        float $remainingUsage,
        int $remainingPeriods,
        string $type,
        string $scenario
    ): array {
        // Get pattern factors for remaining periods
        $patternFactors = [];
        for ($i = $lastActualIndex + 1; $i < $periodLength; $i++) {
            $patternFactor = $this->getPatternMultiplier('day', $i);
            
            // Adjust patterns by scenario
            if ($scenario === 'best') {
                // Best case has lower peaks
                $patternFactor = $patternFactor > 1.0 ? 
                    1.0 + (($patternFactor - 1.0) * 0.7) : $patternFactor;
            } else if ($scenario === 'worst') {
                // Worst case has higher peaks
                $patternFactor = $patternFactor > 1.0 ? 
                    1.0 + (($patternFactor - 1.0) * 1.3) : $patternFactor;
            }
            
            $patternFactors[$i] = $patternFactor;
        }
        
        // Maximum hourly value based on energy type and scenario
        $baseMaxValue = $type === 'electricity' ? 1.2 : 0.6;
        $maxValuePerUnit = $baseMaxValue;
        
        // Adjust max value per scenario
        if ($scenario === 'best') {
            $maxValuePerUnit *= 0.9; // Lower max for best case
        } else if ($scenario === 'worst') {
            $maxValuePerUnit *= 1.2; // Higher max for worst case
        }
        
        // For hourly transitions, create smooth connections
        if ($currentPoint > $lastActualIndex) {
            // Get transition slope based on factors
            $transitionSlope = $this->calculateTransitionSlope(
                $lastActualValue, $patternFactors, $remainingUsage, 'day', $scenario);
            
            // Fill transition points
            for ($i = $lastActualIndex + 1; $i <= $currentPoint; $i++) {
                $steps = $i - $lastActualIndex;
                $predictionLine[$i] = min($lastActualValue + ($transitionSlope * $steps), $maxValuePerUnit);
            }
        }
        
        // Calculate average usage per hour, adjusted for scenario
        $avgPerHour = $remainingUsage / $remainingPeriods;
        $avgPerHour = min($avgPerHour, $maxValuePerUnit * 0.5); // Cap the average
        
        // Start from the last known value
        $lastValue = $predictionLine[$currentPoint] ?? $lastActualValue;
        
        // Generate future hours
        for ($i = $currentPoint + 1; $i < $periodLength; $i++) {
            // Get normalized pattern factor
            $avgPatternFactor = array_sum($patternFactors) / count($patternFactors);
            $relativeFactor = $patternFactors[$i] / max($avgPatternFactor, 0.1);
            
            // Calculate value for this hour
            $hourlyValue = $avgPerHour * $relativeFactor;
            
            // Limit increases between consecutive hours
            $maxIncrease = 0.5; // Maximum kWh increase per hour
            // Adjust max increase by scenario
            if ($scenario === 'best') {
                $maxIncrease *= 0.8;
            } else if ($scenario === 'worst') {
                $maxIncrease *= 1.2;
            }
            
            // Calculate new value with limited increase
            if ($hourlyValue > $lastValue) {
                $proposedValue = $lastValue + min($hourlyValue - $lastValue, $maxIncrease);
            } else {
                $proposedValue = $hourlyValue;
            }
            
            // Apply maximum cap for hourly values
            $predictionLine[$i] = min($proposedValue, $maxValuePerUnit);
            $lastValue = $predictionLine[$i];
        }
        
        // Apply scenario-specific pattern adjustments
        $this->applyHourlyPatternAdjustments($predictionLine, $currentPoint, $scenario);
        
        return $predictionLine;
    }
    
    /**
     * Generate month view prediction line with more realistic patterns
     */
    private function generateMonthLine(
        array $predictionLine,
        float $lastActualValue,
        int $lastActualIndex,
        int $currentPoint,
        int $periodLength,
        float $actualSum,
        float $remainingUsage,
        string $type,
        string $scenario
    ): array {
        // Determine current month for seasonal effects
        $currentMonth = (int)date('n');
        
        // Determine first day of month for weekday effects
        $startDayOfWeek = (int)date('w', strtotime(date('Y-m-01'))); // 0 = Sunday, 6 = Saturday
        
        // Weekday patterns - weekends have higher consumption
        $weekdayPatterns = [
            0 => 1.3,  // Sunday
            1 => 0.9,  // Monday
            2 => 0.85, // Tuesday
            3 => 0.9,  // Wednesday
            4 => 0.95, // Thursday
            5 => 1.1,  // Friday
            6 => 1.25  // Saturday
        ];
        
        // Seasonal adjustment for the selected month
        $seasonalFactor = $type === 'gas' ?
            $this->getSeasonalFactorForGas($currentMonth) :
            $this->getSeasonalFactorForElectricity($currentMonth);
            
        // Adjust trend based on season
        $trendDirection = $this->getTrendDirectionForMonth($currentMonth, $type, $scenario);
        
        // Calculate daily average consumption based on remaining total
        $daysRemaining = $periodLength - $currentPoint - 1;
        if ($daysRemaining <= 0) return $predictionLine;
        
        $averageDailyUsage = $remainingUsage / $daysRemaining;
        
        // Maximum daily value depending on type and scenario
        $maxDailyValue = $type === 'electricity' ? 
            ($scenario === 'worst' ? 30 : 25) :  // Electricity max 25-30 kWh/day
            ($scenario === 'worst' ? 15 : 12);   // Gas max 12-15 m³/day
        
        // Generate realistic daily patterns
        for ($i = $currentPoint + 1; $i < $periodLength; $i++) {
            // Determine day of week for this day
            $dayOfWeek = ($startDayOfWeek + $i) % 7;
            $weekdayFactor = $weekdayPatterns[$dayOfWeek];
            
            // Adjust weekday factor based on scenario
            if ($scenario === 'best') {
                // Best case: less difference between weekdays
                $weekdayFactor = ($weekdayFactor - 1.0) * 0.7 + 1.0;
            } elseif ($scenario === 'worst') {
                // Worst case: more difference between weekdays
                $weekdayFactor = ($weekdayFactor - 1.0) * 1.3 + 1.0;
            }
            
            // Apply trend (increase/decrease over time)
            $dayFromStart = $i - $currentPoint;
            $trendFactor = 1.0 + ($trendDirection * $dayFromStart);
            
            // Set 10% chance for an extreme day (e.g., very cold or hot)
            $extremeDay = mt_rand(1, 10) === 1;
            $extremeFactor = 1.0;
            
            if ($extremeDay) {
                if ($type === 'gas' && ($currentMonth >= 10 || $currentMonth <= 3)) {
                    // Extremely cold day in winter - up to 50% more gas consumption
                    $extremeFactor = mt_rand(130, 150) / 100;
                } elseif ($type === 'electricity' && $currentMonth >= 6 && $currentMonth <= 8) {
                    // Extremely hot day in summer - up to 40% more electricity consumption (AC)
                    $extremeFactor = mt_rand(120, 140) / 100;
                } else {
                    // Other extreme days have less impact
                    $extremeFactor = mt_rand(110, 130) / 100;
                }
                
                // Adjust extreme factor based on scenario
                if ($scenario === 'best') {
                    $extremeFactor = 1.0 + (($extremeFactor - 1.0) * 0.7);
                } elseif ($scenario === 'worst') {
                    $extremeFactor = 1.0 + (($extremeFactor - 1.0) * 1.3);
                }
            }
            
            // Random variation for natural pattern
            $randomFactor = 0.95 + (mt_rand(0, 10) / 100);
            
            // Calculate value for this day
            $predictedValue = $averageDailyUsage * $weekdayFactor * $trendFactor * $extremeFactor * $randomFactor;
            
            // Limit the value to the maximum
            $predictionLine[$i] = min($predictedValue, $maxDailyValue);
        }
        
        // Create a smooth transition from last actual data point
        $this->smoothTransition($predictionLine, $lastActualIndex, $currentPoint);
        
        return $predictionLine;
    }

    /**
     * Helper to create a smooth transition between actual and predicted data
     */
    private function smoothTransition(array &$line, int $lastActualIndex, int $currentPoint): void
    {
        // If we have a gap between last actual point and current point
        if ($currentPoint > $lastActualIndex + 1) {
            $lastValue = $line[$lastActualIndex];
            $nextValue = $line[$currentPoint + 1] ?? $line[$currentPoint];
            
            // Calculate transition values for intermediate points
            $steps = $currentPoint - $lastActualIndex;
            
            for ($i = $lastActualIndex + 1; $i <= $currentPoint; $i++) {
                $progress = ($i - $lastActualIndex) / $steps;
                $line[$i] = $lastValue + ($nextValue - $lastValue) * $progress;
            }
        }
    }
    
    /**
     * Apply a trend adjustment to the prediction line
     * 
     * @param array &$line Line to adjust (passed by reference)
     * @param int $startPoint Starting point for adjustments
     * @param float $slope Slope factor (+/- percentage per unit)
     */
    private function applyTrendToLine(array &$line, int $startPoint, float $slope): void
    {
        for ($i = $startPoint + 1; $i < count($line); $i++) {
            if ($line[$i] === null) continue;
            
            // Calculate adjustment factor based on distance from start
            $distance = $i - $startPoint;
            $adjustmentFactor = 1 + ($slope * $distance);
            
            // Apply adjustment
            $line[$i] *= $adjustmentFactor;
        }
    }
    
    /**
     * Apply hourly pattern adjustments for day view
     */
    private function applyHourlyPatternAdjustments(array &$line, int $currentPoint, string $scenario): void
    {
        // Evening peak hours (18:00-22:00)
        $eveningPeakHours = [18, 19, 20, 21, 22];
        
        // Morning peak hours (7:00-9:00)
        $morningPeakHours = [7, 8, 9];
        
        // Night low hours (0:00-5:00)
        $nightLowHours = [0, 1, 2, 3, 4, 5];
        
        // Apply scenario-specific adjustments
        for ($i = $currentPoint + 1; $i < count($line); $i++) {
            if ($line[$i] === null) continue;
            
            // Evening peak adjustments
            if (in_array($i, $eveningPeakHours)) {
                if ($scenario === 'best') {
                    // Best case: lower evening peak (energy saving)
                    $line[$i] *= 0.9;
                } else if ($scenario === 'worst') {
                    // Worst case: higher evening peak (more consumption)
                    $line[$i] *= 1.15;
                }
            }
            
            // Morning peak adjustments
            if (in_array($i, $morningPeakHours)) {
                if ($scenario === 'best') {
                    // Best case: slightly lower morning peak
                    $line[$i] *= 0.95;
                } else if ($scenario === 'worst') {
                    // Worst case: higher morning peak
                    $line[$i] *= 1.1;
                }
            }
            
            // Night low adjustments
            if (in_array($i, $nightLowHours)) {
                if ($scenario === 'best') {
                    // Best case: even lower night usage
                    $line[$i] *= 0.9;
                } else if ($scenario === 'worst') {
                    // Worst case: higher night usage (less energy efficient)
                    $line[$i] *= 1.25;
                }
            }
        }
    }
    
    /**
     * Calculate transition slope between actual and predicted values
     */
    private function calculateTransitionSlope(
        float $lastActualValue, 
        array $patternFactors, 
        float $remainingUsage, 
        string $period,
        string $scenario
    ): float {
        if (empty($patternFactors)) {
            return 0;
        }
        
        // Get first pattern factor
        $firstKey = array_key_first($patternFactors);
        $firstPatternFactor = $patternFactors[$firstKey];
        
        // Calculate average factor
        $factorSum = array_sum($patternFactors);
        $avgFactor = $factorSum / count($patternFactors);
        
        // Calculate expected usage per factor
        $avgUsagePerFactor = $remainingUsage / max($factorSum, 0.1);
        
        // Calculate expected next value
        $expectedNextValue = $avgUsagePerFactor * $firstPatternFactor;
        
        // Calculate base slope as fraction of difference
        $difference = $expectedNextValue - $lastActualValue;
        $baseSlope = $difference * 0.3;
        
        // Apply scenario adjustments
        if ($scenario === 'best') {
            return $baseSlope * 0.85; // Gentler slope for best case
        } else if ($scenario === 'worst') {
            return $baseSlope * 1.15; // Steeper slope for worst case
        }
        
        return $baseSlope;
    }
    
    /**
     * Find the last valid data point in the actual data
     */
    private function findLastActualDataPoint(array $actualData, int $currentPoint): array
    {
        $lastActualValue = null;
        $lastActualIndex = $currentPoint;
        
        // Look back up to 3 points to find actual data
        for ($i = $currentPoint; $i >= max(0, $currentPoint - 3); $i--) {
            if (isset($actualData[$i]) && $actualData[$i] !== null) {
                $lastActualValue = $actualData[$i];
                $lastActualIndex = $i;
                break;
            }
        }
        
        return [
            'value' => $lastActualValue,
            'index' => $lastActualIndex
        ];
    }
    
    /**
     * Calculate the sum of actual data up to a certain point
     */
    private function calculateActualSum(array $actualData, int $currentPoint): float
    {
        $actualSum = 0;
        for ($i = 0; $i <= $currentPoint; $i++) {
            $actualSum += $actualData[$i] ?? 0;
        }
        return $actualSum;
    }
    
    /**
     * Get scaling factor for different scenarios
     */
    private function getScenarioScaleFactor(string $scenario): float
    {
        switch ($scenario) {
            case 'best':
                return 0.9; // 10% reduction
            case 'worst':
                return 1.2; // 20% increase
            case 'expected':
            default:
                return 1.0;
        }
    }
    
    /**
     * Get the length of the period in data points
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
     * Get pattern multiplier to represent realistic usage patterns
     */
    private function getPatternMultiplier(string $period, int $index): float
    {
        switch ($period) {
            case 'day':
                // Daily pattern: higher consumption in morning and evening
                $hourPatterns = [
                    0.5, 0.4, 0.3, 0.3, 0.3, 0.5, // 0-5 night (low usage)
                    0.8, 1.4, 1.7, 1.3, 1.1, 1.0, // 6-11 morning (peak at 8)
                    1.0, 1.1, 1.0, 1.1, 1.3, 1.5, // 12-17 afternoon/evening
                    2.0, 1.8, 1.5, 1.2, 0.9, 0.7  // 18-23 evening (peak at 18)
                ];
                return $hourPatterns[$index % 24];
                
            case 'month':
                // Monthly pattern: higher on weekends
                // Simulate weekend pattern
                return ($index % 7 == 0 || $index % 7 == 6) ? 1.3 : 1.0;
                
            case 'year':
            default:
                // Yearly pattern: seasonal variations
                $monthPatterns = [
                    1.4, 1.3, 1.1, 0.9, 0.8, 0.7, // Jan-Jun: Higher in winter, lower in spring/summer
                    0.8, 0.9, 1.0, 1.1, 1.2, 1.4  // Jul-Dec: Increasing toward winter again
                ];
                return $monthPatterns[$index % 12];
        }
    }
    
    /**
     * Calculate seasonal factor for gas
     */
    private function getSeasonalFactorForGas(int $month): float
    {
        $factors = [
            1 => 1.8, 2 => 1.7, 3 => 1.5, 4 => 1.2, 5 => 0.8, 6 => 0.5,
            7 => 0.4, 8 => 0.4, 9 => 0.6, 10 => 1.0, 11 => 1.4, 12 => 1.7
        ];
        
        return $factors[$month] ?? 1.0;
    }

    /**
     * Calculate seasonal factor for electricity
     */
    private function getSeasonalFactorForElectricity(int $month): float
    {
        $factors = [
            1 => 1.15, 2 => 1.1, 3 => 1.05, 4 => 0.95, 5 => 0.9, 6 => 0.95,
            7 => 1.05, 8 => 1.05, 9 => 0.95, 10 => 1.0, 11 => 1.05, 12 => 1.15
        ];
        
        return $factors[$month] ?? 1.0;
    }

    /**
     * Determine trend direction based on month, type and scenario
     */
    private function getTrendDirectionForMonth(int $month, string $type, string $scenario): float
    {
        // Base trend based on season
        $baseDirection = 0.0;
        
        if ($type === 'gas') {
            // Gas consumption increases in fall/winter, decreases in spring/summer
            if ($month >= 9 || $month <= 2) {
                $baseDirection = 0.005; // Slight increase per day
            } elseif ($month >= 3 && $month <= 8) {
                $baseDirection = -0.003; // Slight decrease per day
            }
        } else {
            // Electricity has less pronounced seasonal trends
            if ($month >= 10 || $month <= 3) {
                $baseDirection = 0.002; // Very slight increase in winter
            } elseif ($month >= 6 && $month <= 8) {
                $baseDirection = 0.001; // Very slight increase in summer (AC)
            } else {
                $baseDirection = -0.001; // Very slight decrease in spring/fall
            }
        }
        
        // Adjust based on scenario
        if ($scenario === 'best') {
            // Best case: trend direction down (savings)
            if ($baseDirection > 0) {
                $baseDirection *= 0.5; // Weaken increase
            } else {
                $baseDirection *= 1.5; // Strengthen decrease
            }
            
            // Add extra decrease for best case
            $baseDirection -= 0.002;
        } elseif ($scenario === 'worst') {
            // Worst case: trend direction up (more consumption)
            if ($baseDirection > 0) {
                $baseDirection *= 1.5; // Strengthen increase
            } else {
                $baseDirection *= 0.5; // Weaken decrease
            }
            
            // Add extra increase for worst case
            $baseDirection += 0.003;
        }
        
        return $baseDirection;
    }
    
    /**
     * Get estimated value when no actual data is available
     */
    private function getEstimatedValue(string $period, string $type = 'electricity'): float
    {
        // Default values scaled by period and energy type
        $defaults = [
            'electricity' => [
                'day' => 0.3,    // kWh per hour (reasonable for most households)
                'month' => 7,    // kWh per day
                'year' => 250,   // kWh per month
            ],
            'gas' => [
                'day' => 0.15,   // m³ per hour
                'month' => 3,    // m³ per day
                'year' => 100,   // m³ per month
            ]
        ];
        
        return $defaults[$type][$period] ?? $defaults['electricity'][$period] ?? 0.3;
    }
}