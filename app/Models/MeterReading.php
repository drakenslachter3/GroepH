<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MeterReading extends Model
{
    use HasFactory;

    /**
     * De attributen die massaal toegewezen mogen worden.
     *
     * @var array
     */
    protected $fillable = [
        'smart_meter_id',
        'timestamp',
        'electricity_delivered_tariff1',    // 1.0:1.8.1 - kWh
        'electricity_delivered_tariff2',    // 1.0:1.8.2 - kWh
        'electricity_returned_tariff1',     // 1.0:2.8.1 - kWh
        'electricity_returned_tariff2',     // 1.0:2.8.2 - kWh
        'current_electricity_usage',        // 1.0:1.7.0 - kW
        'current_electricity_return',       // 1.0:2.7.0 - kW
        'current_phase_voltage_l1',         // 1.0:32.7.0 - V
        'current_phase_current_l1',         // 1.0:31.7.0 - A
        'instantaneous_active_power_l1_pos', // 1.0:21.7.0 - kW
        'instantaneous_active_power_l1_neg', // 1.0:22.7.0 - kW
        'gas_meter_reading',                // 0.1:24.2.1 - m3
        'raw_data',                         // Full P1 telegram data
        'additional_data'                   // Additional data like solar panels, car charger, etc.
    ];

    /**
     * De attributen die gecast moeten worden.
     *
     * @var array
     */
    protected $casts = [
        'timestamp' => 'datetime',
        'electricity_delivered_tariff1' => 'float',
        'electricity_delivered_tariff2' => 'float',
        'electricity_returned_tariff1' => 'float',
        'electricity_returned_tariff2' => 'float',
        'current_electricity_usage' => 'float',
        'current_electricity_return' => 'float',
        'current_phase_voltage_l1' => 'float',
        'current_phase_current_l1' => 'float',
        'instantaneous_active_power_l1_pos' => 'float',
        'instantaneous_active_power_l1_neg' => 'float',
        'gas_meter_reading' => 'float',
        'additional_data' => 'array'
    ];

    /**
     * Get de slimme meter waartoe deze meting behoort.
     */
    public function smartMeter()
    {
        return $this->belongsTo(SmartMeter::class);
    }

    /**
     * Parse DSMR P1 data and create a new meter reading.
     *
     * @param SmartMeter $smartMeter
     * @param string $jsonData
     * @return MeterReading
     */
    public static function parseAndCreate(SmartMeter $smartMeter, string $jsonData)
    {
        try {
            $data = json_decode($jsonData, true);
            
            if (!isset($data['datagram']['p1'])) {
                throw new \Exception('Invalid DSMR P1 data format: datagram.p1 not found');
            }
            
            $p1Data = $data['datagram']['p1'];
            
            // Clean up the data - sometimes the JSON contains escaped newlines
            $p1Data = str_replace("\\r\\n", "\r\n", $p1Data);
            
            $lines = explode("\r\n", $p1Data);
            
            Log::debug("P1 data lines: " . count($lines));
            
            $readingData = [
                'smart_meter_id' => $smartMeter->id,
                'timestamp' => now(),
                'raw_data' => $p1Data,
                'additional_data' => [
                    's0' => $data['datagram']['s0'] ?? null,
                    's1' => $data['datagram']['s1'] ?? null
                ]
            ];
            
            // Parse DSMR P1 data with improved pattern matching
            foreach ($lines as $index => $line) {
                // Log the line for debugging
                Log::debug("Processing line {$index}: {$line}");
                
                // Normalize line format - different DSMR versions may use different separators
                $line = str_replace(['(', ')'], ['(', ')'], $line);
                
                // Electricity delivered to client - tariff 1 (low)
                if (preg_match('/1[\.-]0:1[\.-]8[\.-]1\((\d+\.\d+)\*kWh\)/', $line, $matches)) {
                    $readingData['electricity_delivered_tariff1'] = (float) $matches[1];
                    Log::debug("Matched electricity_delivered_tariff1: {$matches[1]}");
                }
                
                // Electricity delivered to client - tariff 2 (high)
                elseif (preg_match('/1[\.-]0:1[\.-]8[\.-]2\((\d+\.\d+)\*kWh\)/', $line, $matches)) {
                    $readingData['electricity_delivered_tariff2'] = (float) $matches[1];
                    Log::debug("Matched electricity_delivered_tariff2: {$matches[1]}");
                }
                
                // Electricity delivered by client - tariff 1 (low)
                elseif (preg_match('/1[\.-]0:2[\.-]8[\.-]1\((\d+\.\d+)\*kWh\)/', $line, $matches)) {
                    $readingData['electricity_returned_tariff1'] = (float) $matches[1];
                    Log::debug("Matched electricity_returned_tariff1: {$matches[1]}");
                }
                
                // Electricity delivered by client - tariff 2 (high)
                elseif (preg_match('/1[\.-]0:2[\.-]8[\.-]2\((\d+\.\d+)\*kWh\)/', $line, $matches)) {
                    $readingData['electricity_returned_tariff2'] = (float) $matches[1];
                    Log::debug("Matched electricity_returned_tariff2: {$matches[1]}");
                }
                
                // Current electricity usage
                elseif (preg_match('/1[\.-]0:1[\.-]7[\.-]0\((\d+\.\d+)\*kW\)/', $line, $matches)) {
                    $readingData['current_electricity_usage'] = (float) $matches[1];
                    Log::debug("Matched current_electricity_usage: {$matches[1]}");
                }
                
                // Current electricity delivery
                elseif (preg_match('/1[\.-]0:2[\.-]7[\.-]0\((\d+\.\d+)\*kW\)/', $line, $matches)) {
                    $readingData['current_electricity_return'] = (float) $matches[1];
                    Log::debug("Matched current_electricity_return: {$matches[1]}");
                }
                
                // Current phase current L1
                elseif (preg_match('/1[\.-]0:31[\.-]7[\.-]0\((\d+)\*A\)/', $line, $matches)) {
                    $readingData['current_phase_current_l1'] = (float) $matches[1];
                    Log::debug("Matched current_phase_current_l1: {$matches[1]}");
                }
                
                // Instantaneous active power L1 (+P)
                elseif (preg_match('/1[\.-]0:21[\.-]7[\.-]0\((\d+\.\d+)\*kW\)/', $line, $matches)) {
                    $readingData['instantaneous_active_power_l1_pos'] = (float) $matches[1];
                    Log::debug("Matched instantaneous_active_power_l1_pos: {$matches[1]}");
                }
                
                // Instantaneous active power L1 (-P)
                elseif (preg_match('/1[\.-]0:22[\.-]7[\.-]0\((\d+\.\d+)\*kW\)/', $line, $matches)) {
                    $readingData['instantaneous_active_power_l1_neg'] = (float) $matches[1];
                    Log::debug("Matched instantaneous_active_power_l1_neg: {$matches[1]}");
                }
                
                // Gas meter reading - multiple patterns to handle different DSMR versions
                elseif (preg_match('/0[\.-]1:24[\.-]2[\.-]1\((.+?)\)\((\d+\.\d+)\*m3\)/', $line, $matches)) {
                    $readingData['gas_meter_reading'] = (float) $matches[2];
                    Log::debug("Matched gas_meter_reading (pattern 1): {$matches[2]}");
                }
                elseif (preg_match('/0[\.-]1:24[\.-]2[\.-]1\((.+?)\((\d+\.\d+)\*m3\)/', $line, $matches)) {
                    $readingData['gas_meter_reading'] = (float) $matches[2];
                    Log::debug("Matched gas_meter_reading (pattern 2): {$matches[2]}");
                }
                // This pattern is specific to the example in the prompt
                elseif (preg_match('/0[\.-]1:24[\.-]2[\.-]1\((\d+)(\d+)(\d+)(\d+)([^()]+)\((\d+\.\d+)\*m3\)/', $line, $matches)) {
                    $readingData['gas_meter_reading'] = (float) $matches[6];
                    Log::debug("Matched gas_meter_reading (pattern 3): {$matches[6]}");
                }
            }
            
            // Log the reading data
            Log::info('Parsed reading data: ' . json_encode($readingData));
            
            // Create and return the meter reading
            return self::create($readingData);
        } catch (\Exception $e) {
            // Log the error and rethrow
            Log::error('Error parsing DSMR P1 data: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('JSON data: ' . $jsonData);
            throw $e;
        }
    }
    
    /**
     * Get total delivered electricity (tariff 1 + tariff 2)
     * 
     * @return float
     */
    public function getTotalElectricityDelivered()
    {
        return ($this->electricity_delivered_tariff1 ?: 0) + 
               ($this->electricity_delivered_tariff2 ?: 0);
    }
    
    /**
     * Get total returned electricity (tariff 1 + tariff 2)
     * 
     * @return float
     */
    public function getTotalElectricityReturned()
    {
        return ($this->electricity_returned_tariff1 ?: 0) + 
               ($this->electricity_returned_tariff2 ?: 0);
    }
}