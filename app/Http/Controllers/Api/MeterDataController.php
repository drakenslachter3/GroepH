<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MeterReading;
use App\Models\SmartMeter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MeterDataController extends Controller
{
    /**
     * Process incoming meter data via API
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'meter_id' => 'required|string|exists:smart_meters,meter_id',
                'data' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find the smart meter
            $smartMeter = SmartMeter::where('meter_id', $request->meter_id)->first();
            
            if (!$smartMeter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Smart meter not found'
                ], 404);
            }

            // Decode JSON to check if it's in the expected format
            $data = json_decode($request->data, true);
            if (!$data || !isset($data['datagram']) || !isset($data['datagram']['p1'])) {
                Log::error('Invalid data format received: ' . $request->data);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data format. Expected P1 telegram data in datagram.p1'
                ], 400);
            }

            // Parse and store meter reading
            $meterReading = MeterReading::parseAndCreate($smartMeter, $request->data);
            
            // Update the smart meter's last reading date
            $smartMeter->last_reading_date = now();
            $smartMeter->save();

            return response()->json([
                'success' => true,
                'message' => 'Meter reading successfully processed',
                'data' => [
                    'reading_id' => $meterReading->id,
                    'timestamp' => $meterReading->timestamp,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error processing meter data: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing meter data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the latest reading for a specific meter
     * 
     * @param Request $request
     * @param string $meterId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatestReading(Request $request, string $meterId)
    {
        try {
            $smartMeter = SmartMeter::where('meter_id', $meterId)->first();
            
            if (!$smartMeter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Smart meter not found'
                ], 404);
            }

            $latestReading = $smartMeter->readings()->latest('timestamp')->first();
            
            if (!$latestReading) {
                return response()->json([
                    'success' => false,
                    'message' => 'No readings available for this meter'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reading_id' => $latestReading->id,
                    'timestamp' => $latestReading->timestamp,
                    'electricity' => [
                        'delivered' => [
                            'tariff1' => $latestReading->electricity_delivered_tariff1,
                            'tariff2' => $latestReading->electricity_delivered_tariff2,
                            'total' => ($latestReading->electricity_delivered_tariff1 + $latestReading->electricity_delivered_tariff2)
                        ],
                        'returned' => [
                            'tariff1' => $latestReading->electricity_returned_tariff1,
                            'tariff2' => $latestReading->electricity_returned_tariff2,
                            'total' => ($latestReading->electricity_returned_tariff1 + $latestReading->electricity_returned_tariff2)
                        ],
                        'current_usage' => $latestReading->current_electricity_usage,
                        'current_return' => $latestReading->current_electricity_return
                    ],
                    'gas' => [
                        'reading' => $latestReading->gas_meter_reading
                    ],
                    'additional_data' => $latestReading->additional_data
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving meter reading: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving meter reading',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Test endpoint to validate P1 telegram parsing
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testParsing(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'data' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Decode JSON to check format
            $data = json_decode($request->data, true);
            if (!$data || !isset($data['datagram']) || !isset($data['datagram']['p1'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data format. Expected P1 telegram data in datagram.p1'
                ], 400);
            }

            // Test parsing without saving to database
            $p1Data = $data['datagram']['p1'];
            $lines = explode("\r\n", $p1Data);
            
            $parsedData = [
                'timestamp' => now(),
                'additional_data' => [
                    's0' => $data['datagram']['s0'] ?? null,
                    's1' => $data['datagram']['s1'] ?? null
                ]
            ];
            
            // Parse DSMR P1 data
            foreach ($lines as $line) {
                // Match patterns for various data points
                if (preg_match('/1\.0:1\.8\.1\((\d+\.\d+)\*kWh\)/', $line, $matches)) {
                    $parsedData['electricity_delivered_tariff1'] = (float) $matches[1];
                }
                
                elseif (preg_match('/1\.0:1\.8\.2\((\d+\.\d+)\*kWh\)/', $line, $matches)) {
                    $parsedData['electricity_delivered_tariff2'] = (float) $matches[1];
                }
                
                elseif (preg_match('/1\.0:2\.8\.1\((\d+\.\d+)\*kWh\)/', $line, $matches)) {
                    $parsedData['electricity_returned_tariff1'] = (float) $matches[1];
                }
                
                elseif (preg_match('/1\.0:2\.8\.2\((\d+\.\d+)\*kWh\)/', $line, $matches)) {
                    $parsedData['electricity_returned_tariff2'] = (float) $matches[1];
                }
                
                elseif (preg_match('/1\.0:1\.7\.0\((\d+\.\d+)\*kW\)/', $line, $matches)) {
                    $parsedData['current_electricity_usage'] = (float) $matches[1];
                }
                
                elseif (preg_match('/1\.0:2\.7\.0\((\d+\.\d+)\*kW\)/', $line, $matches)) {
                    $parsedData['current_electricity_return'] = (float) $matches[1];
                }
                
                elseif (preg_match('/1\.0:31\.7\.0\((\d+)\*A\)/', $line, $matches)) {
                    $parsedData['current_phase_current_l1'] = (float) $matches[1];
                }
                
                elseif (preg_match('/1\.0:21\.7\.0\((\d+\.\d+)\*kW\)/', $line, $matches)) {
                    $parsedData['instantaneous_active_power_l1_pos'] = (float) $matches[1];
                }
                
                elseif (preg_match('/1\.0:22\.7\.0\((\d+\.\d+)\*kW\)/', $line, $matches)) {
                    $parsedData['instantaneous_active_power_l1_neg'] = (float) $matches[1];
                }
                
                // Gas meter reading with better pattern matching
                elseif (preg_match('/0\.1:24\.2\.1\(([^)]+)\)\((\d+\.\d+)\*m3\)/', $line, $matches)) {
                    $parsedData['gas_meter_reading'] = (float) $matches[2];
                }
                elseif (preg_match('/0\.1:24\.2\.1\(([^(]+)\((\d+\.\d+)\*m3\)/', $line, $matches)) {
                    $parsedData['gas_meter_reading'] = (float) $matches[2];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully parsed P1 data',
                'parsed_data' => $parsedData
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error testing P1 data parsing: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error testing P1 data parsing',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}