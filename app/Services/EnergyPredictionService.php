<?php

namespace App\Services;

/**
 * Class EnergyPredictionService
 * 
 * Service for predicting energy usage based on historical data
 */
class EnergyPredictionService
{
    /**
     * Prediction line generator
     */
    private $lineGenerator;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lineGenerator = new EnergyPredictionLineGenerator();
    }
    
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
        // Calculate basic trend and average
        $recentData = array_slice($historicalData, -3);
        $trend = $this->calculateTrend($recentData);
        $averageUsage = $this->calculateAverage($historicalData);
        $seasonalFactor = $this->getSeasonalFactor($period, $type);
        
        // Get the period length for the projection
        $periodLength = $this->getPeriodLength($period);
        
        // Calculate the base prediction with appropriate scaling based on period
        $scaleFactor = $this->getScaleFactor($period);
        
        // If historical data is available, base prediction on it
        if (!empty($historicalData)) {
            // Get the trend from last few data points
            $lastValues = array_filter($historicalData, function($value) {
                return $value !== null;
            });
            
            if (!empty($lastValues)) {
                // Use average of actual values as base for prediction
                // Limit the trend influence to avoid excessive growth/decline
                $limitedTrend = min(max($trend, -0.15), 0.15); // Limit trend to ±15%
                $predictedUsage = $this->calculateAverage($lastValues) * (1 + $limitedTrend) * $seasonalFactor;
            } else {
                // Fallback if no valid historical data
                $predictedUsage = $scaleFactor * $seasonalFactor;
            }
        } else {
            // Fallback if no historical data
            $predictedUsage = $scaleFactor * $seasonalFactor;
        }
        
        // Make sure prediction is reasonable based on scale
        $predictedUsage = $this->normalizeValue($predictedUsage, $period, $type);
        
        // Calculate remaining factor
        $remainingFactor = $this->getRemainingFactor($period);
        
        // Calculate current point in period
        $currentPoint = $this->getCurrentPositionInPeriod($period);
        
        // Calculate actual sum so far
        $actualSum = 0;
        foreach ($historicalData as $i => $value) {
            if ($i <= $currentPoint && $value !== null) {
                $actualSum += $value;
            }
        }
        
        // Prediction logic based on period type
        if ($period === 'year') {
            // For year view, calculate more stable predictions based on actual data pattern
            $actualValues = array_filter($historicalData, function($val) { return $val !== null; });
            
            if (!empty($actualValues)) {
                // Calculate average monthly value as base for prediction
                $avgMonthlyValue = array_sum($actualValues) / count($actualValues);
                
                // Average annual consumption based on current data
                $estimatedAnnual = $avgMonthlyValue * 12;
                
                // Apply a modest trend factor with stricter limits
                $trendFactor = 1 + (min(max($trend, -0.10), 0.10)); // Limit trend to ±10%
                
                // Predicted total is average x 12 x trend factor
                $predictedTotalUsage = $estimatedAnnual * $trendFactor;
            } else {
                // Fallback to reasonable yearly values if no actual data
                $predictedTotalUsage = $type === 'electricity' ? 3500 : 1500;
            }
            
            // Ensure the prediction is within reasonable bounds
            $minYearly = $type === 'electricity' ? 2000 : 800;
            $maxYearly = $type === 'electricity' ? 5000 : 2500;
            $predictedTotalUsage = min(max($predictedTotalUsage, $minYearly), $maxYearly);
        } else if ($period === 'day') {
            // For day view, calculate predicted total differently to keep hourly values realistic
            $hoursLeft = 24 - ($currentPoint + 1);
            
            // Get reasonable hourly max for this energy type
            $hourlyMax = $type === 'electricity' ? 1.2 : 0.6;
            
            // Calculate realistic remaining usage by considering typical pattern
            $remainingUsageEstimate = 0;
            for ($hour = $currentPoint + 1; $hour < 24; $hour++) {
                $patternFactor = $this->getPatternMultiplier($period, $hour);
                $hourlyEstimate = min($predictedUsage * $patternFactor, $hourlyMax);
                $remainingUsageEstimate += $hourlyEstimate;
            }
            
            // Predicted total is actual so far plus realistic remaining
            $predictedTotalUsage = $actualSum + $remainingUsageEstimate;
        } else {
            // For month view, use an improved approach
            // Start with actual usage to date
            $predictedTotalUsage = $actualSum;
            
            // Calculate average usage per day for prediction
            $daysInMonth = date('t');
            $currentDay = min((int)date('j'), $daysInMonth);
            $daysLeft = $daysInMonth - $currentDay;
            
            if ($daysLeft > 0) {
                // Calculate average daily usage so far
                $avgDailyUsage = $currentDay > 0 ? $actualSum / $currentDay : 0;
                
                // Apply seasonal and pattern adjustments for remaining days
                // with a small trend factor for realism
                $trendFactor = 1 + (min(max($trend, -0.05), 0.05)); // Limit trend to ±5% for month view
                $remainingUsage = $avgDailyUsage * $daysLeft * $trendFactor * $seasonalFactor;
                
                // Add predicted remaining usage
                $predictedTotalUsage += $remainingUsage;
            }
        }
        
        // Calculate confidence based on data quality, volatility, and period
        $confidence = $this->calculateConfidence($historicalData, $period);
        
        // Calculate margin based on confidence and period - with improved scaling
        // Smaller margins for day/month view, slightly larger for year
        $baseMarginByPeriod = [
            'day' => 0.05,    // 5% base margin for day (hourly predictions should be tight)
            'month' => 0.08,  // 8% base margin for month
            'year' => 0.12    // 12% base margin for year (reduced from 20%)
        ];
        
        $baseMargin = $baseMarginByPeriod[$period] ?? 0.10;
        
        // Adjust margin based on confidence (lower confidence = wider margin, but keep it reasonable)
        // Use a gentler curve for margin adjustment
        $confidenceAdjustment = ((100 - $confidence) / 100) * 0.12; // 0-12% additional margin based on confidence
        $marginPercentage = $baseMargin + $confidenceAdjustment;
        
        // Cap margins by period type to prevent unrealistic predictions
        if ($period === 'day') {
            $marginPercentage = min($marginPercentage, 0.10); // Cap at 10% for day view
        } else if ($period === 'month') {
            $marginPercentage = min($marginPercentage, 0.18); // Cap at 18% for month view
        } else {
            $marginPercentage = min($marginPercentage, 0.25); // Cap at 25% for year view (reduced from 35%)
        }
        
        // Calculate best and worst case scenarios based on margin
        // Make best case closer to expected for a more optimistic view
        $bestCaseMargin = $marginPercentage * 0.8; // 80% of the full margin for best case
        $worstCaseMargin = $marginPercentage * 1.1; // 110% of the full margin for worst case
        
        $bestCaseUsage = $predictedTotalUsage * (1 - $bestCaseMargin);
        $worstCaseUsage = $predictedTotalUsage * (1 + $worstCaseMargin);
        
        // Generate trend lines for visualization with proper scaling for the period
        $actualData = $this->generateActualData($historicalData, $period);
        
        // Use the new prediction line generator for each scenario
        $predictionLine = $this->lineGenerator->generateLine(
            $actualData, $predictedTotalUsage, $period, $type, 'expected');
        
        $bestCaseLine = $this->lineGenerator->generateLine(
            $actualData, $bestCaseUsage, $period, $type, 'best');
            
        $worstCaseLine = $this->lineGenerator->generateLine(
            $actualData, $worstCaseUsage, $period, $type, 'worst');
        
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
     * Normalize a value to be reasonable for the given period and energy type
     * 
     * @param float $value The value to normalize
     * @param string $period The period type
     * @param string $type The energy type
     * @return float The normalized value
     */
    private function normalizeValue(float $value, string $period, string $type): float
    {
        // Define more realistic ranges for each period and type
        $ranges = [
            'electricity' => [
                'day' => ['min' => 0.1, 'max' => 1.0, 'typical' => 0.4],   // kWh per hour (most households 0.2-0.8)
                'month' => ['min' => 2, 'max' => 12, 'typical' => 7],      // kWh per day (reduced max from 15)
                'year' => ['min' => 80, 'max' => 350, 'typical' => 250]    // kWh per month (reduced max from 400)
            ],
            'gas' => [
                'day' => ['min' => 0.05, 'max' => 0.5, 'typical' => 0.2],  // m³ per hour (reduced max from 0.6)
                'month' => ['min' => 1, 'max' => 8, 'typical' => 4],       // m³ per day (reduced max from 10)
                'year' => ['min' => 30, 'max' => 180, 'typical' => 100]    // m³ per month (reduced max from 200)
            ]
        ];
        
        // Get range for the current period and type
        $range = $ranges[$type][$period] ?? $ranges['electricity']['year'];
        
        // If value outside range, normalize it
        if ($value < $range['min']) {
            return $range['min'];
        } elseif ($value > $range['max']) {
            return $range['max'];
        }
        
        return $value;
    } 
    
    /**
     * Generate actual data points based on historical data and period type
     * With realistic values that fit properly in the chart view
     * 
     * @param array $historicalData Historical usage data
     * @param string $period Period type ('day', 'month', 'year')
     * @return array Array of actual data points for the chart
     */
    private function generateActualData(array $historicalData, string $period): array
    {
        $periodLength = $this->getPeriodLength($period);
        $actualData = [];
        
        // Scale factor for different periods to ensure consistent visualization
        $scaleFactor = $this->getScaleFactor($period);
        
        // Use historical data where available, apply appropriate scaling otherwise
        for ($i = 0; $i < $periodLength; $i++) {
            // Apply pattern multipliers based on period type
            $patternMultiplier = $this->getPatternMultiplier($period, $i);
            
            // Base value with appropriate scale for the period
            $baseValue = isset($historicalData[$i]) ? $historicalData[$i] : ($scaleFactor * $patternMultiplier);
            
            // Apply less randomness for more stable data
            $actualData[$i] = $baseValue * (0.95 + (mt_rand(0, 10) / 100));
        }
        
        // Determine current position in period
        $currentPoint = $this->getCurrentPositionInPeriod($period);
        
        // Null out future data points (we don't have actual data for the future)
        for ($i = $currentPoint + 1; $i < $periodLength; $i++) {
            $actualData[$i] = null;
        }
        
        // For month view: ensure data points are within reasonable range
        if ($period === 'month') {
            for ($i = 0; $i <= $currentPoint; $i++) {
                if ($actualData[$i] > 20) {  // Reduced threshold from 25
                    $actualData[$i] = mt_rand(150, 200) / 10; // Scale to reasonable 15-20 kWh range
                }
            }
        }
        
        // For year view: ensure monthly values are within reasonable range
        if ($period === 'year') {
            for ($i = 0; $i <= $currentPoint; $i++) {
                if ($actualData[$i] > 400) {  // Reduced threshold from 450
                    $actualData[$i] = mt_rand(2000, 3500) / 10; // Scale to reasonable 200-350 kWh range
                }
            }
        }
        
        return $actualData;
    }
    
    /**
     * Get appropriate scale factor based on period to ensure consistent visualization across periods
     * 
     * @param string $period Period type
     * @return float Scale factor for the period
     */
    private function getScaleFactor(string $period): float
    {
        // An average yearly household electricity consumption is around 3500 kWh
        $yearlyAverage = 3500;
        
        switch ($period) {
            case 'day':
                // Daily average (yearly / 365)
                return $yearlyAverage / 365;
            case 'month':
                // Monthly average (yearly / 12)
                return $yearlyAverage / 12;
            case 'year':
            default:
                // Yearly data doesn't need scaling
                return $yearlyAverage;
        }
    }
    
    /**
     * Get pattern multiplier to represent realistic usage patterns within each period
     * 
     * @param string $period Period type
     * @param int $index Index within the period
     * @return float Pattern multiplier (0.5-2.0 typical range)
     */
    private function getPatternMultiplier(string $period, int $index): float
    {
        switch ($period) {
            case 'day':
                // Daily pattern: higher consumption in morning and evening
                // Reduced extreme peaks for more realistic predictions
                $hourPatterns = [
                    0.5, 0.4, 0.3, 0.3, 0.3, 0.5, // 0-5 night (low usage)
                    0.8, 1.3, 1.5, 1.2, 1.0, 0.9, // 6-11 morning (reduced peak at 8 from 1.7 to 1.5)
                    0.9, 1.0, 0.9, 1.0, 1.2, 1.4, // 12-17 afternoon/evening
                    1.7, 1.6, 1.4, 1.1, 0.9, 0.7  // 18-23 evening (reduced peak at 18 from 2.0 to 1.7)
                ];
                return $hourPatterns[$index % 24];
                
            case 'month':
                // Monthly pattern: higher on weekends
                // Calculate the day of week (0 = Sunday, 6 = Saturday)
                $dayOfMonth = $index + 1;
                $currentMonth = (int)date('m');
                $currentYear = (int)date('Y');
                $date = "$currentYear-$currentMonth-$dayOfMonth";
                $dayOfWeek = date('w', strtotime($date));
                
                // Weekend multiplier (reduced from 1.3 to 1.2 for more consistency)
                if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                    return 1.2; // Higher usage on weekends
                }
                return 1.0; // Normal usage on weekdays
                
            case 'year':
            default:
                // Yearly pattern: seasonal variations (reduced extremes)
                $monthPatterns = [
                    1.3, 1.2, 1.1, 0.9, 0.8, 0.7, // Jan-Jun: Higher in winter, lower in spring/summer
                    0.8, 0.9, 0.9, 1.0, 1.1, 1.3  // Jul-Dec: Increasing toward winter again
                ];
                return $monthPatterns[$index % 12];
        }
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
        
        // Filter out null values
        $data = array_filter($data, function($val) { return $val !== null; });
        
        if (count($data) < 2) {
            return 0;
        }
        
        $first = reset($data);
        $last = end($data);
        
        if ($first == 0) {
            return 0;
        }
        
        // Limit extreme trends for more stability
        $rawTrend = ($last - $first) / $first;
        return min(max($rawTrend, -0.3), 0.3); // Limit to ±30%
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
        
        // Filter out null values
        $validData = array_filter($data, function($val) { return $val !== null; });
        
        if (empty($validData)) {
            return 0;
        }
        
        return array_sum($validData) / count($validData);
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
            // Gas heeft sterke seizoensinvloed, maar reduceer extremen
            $winterMonths = [1, 2, 3, 11, 12];
            $summerMonths = [6, 7, 8];
            
            if (in_array($month, $winterMonths)) {
                return 1.3; // Hoger verbruik in winter (reduced from 1.5)
            } elseif (in_array($month, $summerMonths)) {
                return 0.6; // Lager verbruik in zomer (increased from 0.5)
            } else {
                return 1.0; // Gemiddeld verbruik in lente/herfst
            }
        } else {
            // Elektriciteit heeft minder seizoensinvloed
            $winterMonths = [11, 12, 1, 2];
            $summerMonths = [6, 7, 8];
            
            if (in_array($month, $winterMonths)) {
                return 1.15; // Iets hoger verbruik in winter (reduced from 1.2)
            } elseif (in_array($month, $summerMonths)) {
                return 1.08; // Iets hoger verbruik in zomer (reduced from 1.1)
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
     * Dit percentage varieert logisch per periode (dag/maand/jaar)
     *
     * @param array $data Array van historische datapunten
     * @param string $period Period type ('day', 'month', 'year')
     * @return int Betrouwbaarheidspercentage (0-100)
     */
    private function calculateConfidence(array $data, string $period = 'year'): int
    {
        // Improved base confidence values per period
        // Day predictions are most accurate, year predictions have more variables
        $periodBaseConfidence = [
            'day' => 90,    // Increased from 85
            'month' => 82,  // Increased from 75
            'year' => 72    // Increased from 65
        ];
        
        $baseConfidence = $periodBaseConfidence[$period] ?? 72;
        
        // Filter out null values
        $validData = array_filter($data, function($val) { return $val !== null; });
        $validDataCount = count($validData);
        
        // More data points increase confidence (up to +8%)
        $dataPointsBonus = min($validDataCount, 8);
        
        // Consistent data increases confidence (up to +10%)
        $volatility = $this->calculateVolatility($validData);
        $consistencyBonus = $volatility < 0.2 ? 10 : ($volatility < 0.4 ? 7 : ($volatility < 0.7 ? 4 : 0));
        
        // Recent data increases confidence (up to +5%)
        $recencyBonus = $validDataCount > 0 && end($validData) !== false ? 5 : 0;
        
        // Seasonal effect on confidence
        $seasonalEffect = $this->getSeasonalConfidenceEffect($period);
        
        // Final confidence score capped based on period
        $maxConfidence = [
            'day' => 98,    // Day predictions can be very accurate
            'month' => 93,  // Month predictions have slight uncertainty
            'year' => 85    // Year predictions always have uncertainty (reduced from 90)
        ];
        
        return min($baseConfidence + $dataPointsBonus + $consistencyBonus + $recencyBonus + $seasonalEffect, $maxConfidence[$period] ?? 85);
    }
    
    /**
     * Get seasonal effect on prediction confidence
     * 
     * @param string $period Period type
     * @return int Confidence adjustment (-5 to +5)
     */
    private function getSeasonalConfidenceEffect(string $period): int
    {
        $month = (int)date('n');
        $hour = (int)date('G');
        
        switch ($period) {
            case 'day':
                // More predictable during typical hours, less at transition times
                if (($hour >= 9 && $hour <= 15) || ($hour >= 22 || $hour <= 4)) {
                    return 3; // Stable periods (mid-day or night) (reduced from 5)
                } elseif (($hour >= 6 && $hour <= 8) || ($hour >= 17 && $hour <= 21)) {
                    return -3; // Transition periods (morning or evening) (reduced from -5)
                }
                return 0;
                
            case 'month':
                // Mid-month is more predictable than start/end
                $day = (int)date('j');
                $daysInMonth = (int)date('t');
                
                if ($day > 5 && $day < ($daysInMonth - 5)) {
                    return 2; // Mid-month is more stable (reduced from 3)
                } elseif ($day <= 3 || $day >= ($daysInMonth - 2)) {
                    return -2; // Beginning/end of month has more variations (reduced from -3)
                }
                return 0;
                
            case 'year':
            default:
                // Mid-seasons are more predictable than transition months
                if (in_array($month, [1, 2, 7, 8])) {
                    return 2; // Mid-winter and mid-summer are stable (reduced from 3)
                } elseif (in_array($month, [3, 4, 9, 10])) {
                    return -2; // Season transitions are less predictable (reduced from -3)
                }
                return 0;
        }
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
            return 0.8; // Moderate volatility by default for insufficient data (reduced from 1.0)
        }
        
        $mean = $this->calculateAverage($data);
        
        if ($mean == 0) {
            return 0.8; // Avoid division by zero (reduced from 1.0)
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
        
        // Filter out null values
        $data = array_filter($data, function($val) { return $val !== null; });
        
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
                $potentialSaving = round($peakPercentage * 0.25); // 25% van piekverbruik kan worden bespaard (reduced from 30%)
                
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