<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Add debugging functionality to the DashboardPredictionService
 */
class EnhancedDashboardPredictionService extends DashboardPredictionService
{
    /**
     * Override the getDashboardPredictionDataWithRealData method to add logging
     */
    public function getDashboardPredictionDataWithRealData(
        string $type,
        string $period,
        string $date,
        array $influxData
    ): array {
        // Log the input data to debug
        Log::debug("EnhancedDashboardPredictionService: Processing {$type} data for {$period} on {$date}");
        
        // Log the structure of influxData
        Log::debug("InfluxData structure: " . json_encode(array_keys($influxData)));
        
        // Extract the correct field from InfluxDB data based on type
        $actualDataKey = $type === 'electricity' ? 'energy_consumed' : 'gas_delivered';
        
        // Check if the expected key exists
        if (isset($influxData['current_data'][$actualDataKey])) {
            Log::debug("Found {$actualDataKey} data in InfluxDB response. Sample: " . 
                json_encode(array_slice($influxData['current_data'][$actualDataKey], 0, 3)));
        } else {
            Log::warning("Missing {$actualDataKey} in InfluxDB data. Available keys: " . 
                json_encode(isset($influxData['current_data']) ? array_keys($influxData['current_data']) : []));
        }
        
        // Call the parent method to get the prediction data
        $result = parent::getDashboardPredictionDataWithRealData($type, $period, $date, $influxData);
        
        // Log the result structure
        Log::debug("Prediction result structure for {$type}: " . json_encode(array_keys($result)));
        
        return $result;
    }
}