<?php

namespace App\Console\Commands;

use App\Models\InfluxData;
use App\Models\SmartMeter;
use App\Services\InfluxDBService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DebugInfluxDBIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'influxdb:debug 
                           {--raw-query : Execute a raw Flux query for debugging}
                           {--test-influx : Test the connection to InfluxDB}
                           {--test-mysql : Test storing and retrieving from MySQL}
                           {--test-pipeline : Test the entire pipeline from InfluxDB to MySQL}
                           {--meter_id= : Smart meter ID to use for testing}
                           {--period=day : Period to use (day, month, year)}
                           {--date= : Date to use (YYYY-MM-DD format, defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug and test the InfluxDB to MySQL integration';

    /**
     * The InfluxDB service.
     */
    protected $influxService;

    /**
     * Create a new command instance.
     */
    public function __construct(InfluxDBService $influxService)
    {
        parent::__construct();
        $this->influxService = $influxService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('InfluxDB Integration Debugging Tool');
        $this->info('==================================');

        // If no specific options provided, run all tests
        $runAll = !$this->option('raw-query') && 
                  !$this->option('test-influx') && 
                  !$this->option('test-mysql') && 
                  !$this->option('test-pipeline');

        // Test InfluxDB connection
        if ($this->option('test-influx') || $runAll) {
            $this->testInfluxDBConnection();
        }

        // Test MySQL connection and operations
        if ($this->option('test-mysql') || $runAll) {
            $this->testMySQLOperations();
        }

        // Test raw query
        if ($this->option('raw-query')) {
            $this->executeRawQuery();
        }

        // Test full pipeline
        if ($this->option('test-pipeline') || $runAll) {
            $this->testPipeline();
        }

        $this->info('Debug complete.');
    }

    /**
     * Test connection to InfluxDB and basic query
     */
    protected function testInfluxDBConnection()
    {
        $this->info('Testing InfluxDB Connection...');
        
        try {
            $connectionSuccess = $this->influxService->testConnection();
            
            if ($connectionSuccess) {
                $this->info('✅ InfluxDB connection successful');
                
                // Check configuration
                $this->info('InfluxDB Configuration:');
                $this->table(
                    ['Setting', 'Value'],
                    [
                        ['URL', config('influxdb.url')],
                        ['Organization', config('influxdb.org')],
                        ['Bucket', config('influxdb.bucket')],
                        ['Token', substr(config('influxdb.token'), 0, 5) . '...' . substr(config('influxdb.token'), -5)],
                    ]
                );
                
                // Try a simple query
                $this->info('Testing simple query...');
                $query = "from(bucket: \"" . config('influxdb.bucket') . "\") |> range(start: -1h) |> limit(n: 5)";
                $results = $this->influxService->query($query);
                
                if (empty($results)) {
                    $this->warn('Query executed but returned no results. This may be normal if there is no recent data.');
                } else {
                    $this->info('✅ Query successful, returned ' . count($results) . ' tables.');
                    
                    // Display first record if available
                    if (isset($results[0]) && isset($results[0]->records) && !empty($results[0]->records)) {
                        $this->info('Sample record:');
                        $this->line(json_encode($results[0]->records[0]->values, JSON_PRETTY_PRINT));
                    }
                }
            } else {
                $this->error('❌ Failed to connect to InfluxDB. Check your configuration.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception during InfluxDB connection test: ' . $e->getMessage());
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
    
    /**
     * Test MySQL operations
     */
    protected function testMySQLOperations()
    {
        $this->info('Testing MySQL Operations...');
        
        try {
            // Test database connection
            DB::connection()->getPdo();
            $this->info('✅ MySQL connection successful');
            
            // Test write/read operations
            $this->info('Testing write/read operations...');
            
            // Create test record
            $testData = InfluxData::create([
                'measurement' => 'test_measurement',
                'tags' => ['test' => 'tag', 'environment' => 'debug'],
                'fields' => ['value' => 42, 'test_array' => [1, 2, 3, 4, 5]],
                'time' => now(),
            ]);
            
            $this->info('✅ Created test record with ID: ' . $testData->id);
            
            // Read back record
            $readData = InfluxData::find($testData->id);
            
            if ($readData) {
                $this->info('✅ Successfully read test record');
                $this->line('Record data:');
                $this->line(json_encode([
                    'id' => $readData->id,
                    'measurement' => $readData->measurement,
                    'tags' => $readData->tags,
                    'fields' => $readData->fields,
                    'time' => $readData->time,
                ], JSON_PRETTY_PRINT));
                
                // Test serialization/deserialization of JSON fields
                if (isset($readData->fields['test_array']) && is_array($readData->fields['test_array'])) {
                    $this->info('✅ JSON serialization/deserialization working correctly');
                } else {
                    $this->error('❌ JSON serialization/deserialization issue detected');
                }
                
                // Clean up test record
                $readData->delete();
                $this->info('✅ Test record deleted');
            } else {
                $this->error('❌ Failed to read test record');
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception during MySQL test: ' . $e->getMessage());
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
    
    /**
     * Execute a raw Flux query
     */
    protected function executeRawQuery()
    {
        $this->info('Raw Flux Query Execution');
        
        // Get bucket from config
        $bucket = config('influxdb.bucket');
        
        // Choose a query based on user input
        $queryType = $this->choice(
            'What type of query would you like to run?',
            [
                'recent' => 'Recent data (last hour)',
                'meter' => 'Data for a specific meter',
                'custom' => 'Custom query'
            ],
            'recent'
        );
        
        // Build query
        $query = '';
        switch ($queryType) {
            case 'recent':
                $query = "from(bucket: \"{$bucket}\") |> range(start: -1h) |> limit(n: 20)";
                break;
                
            case 'meter':
                $meterId = $this->option('meter_id');
                $range = $this->choice('Time range', ['1h', '24h', '7d', '30d'], '24h');
                $query = "from(bucket: \"{$bucket}\") 
                          |> range(start: -{$range}) 
                          |> filter(fn: (r) => r.meter_id == \"{$meterId}\") 
                          |> limit(n: 100)";
                break;
                
            case 'custom':
                $query = $this->ask('Enter your Flux query');
                break;
        }
        
        $this->info('Executing query:');
        $this->line($query);
        
        try {
            $startTime = microtime(true);
            $results = $this->influxService->query($query);
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->info("Query completed in {$duration} seconds");
            
            if (empty($results)) {
                $this->warn('Query returned no results');
                return;
            }
            
            $this->info('Results:');
            foreach ($results as $tableIndex => $table) {
                $this->info("Table {$tableIndex} - " . (isset($table->records) ? count($table->records) : 0) . " records");
                
                if (isset($table->records) && !empty($table->records)) {
                    // Display first record schema (keys)
                    $firstRecord = $table->records[0];
                    $this->line('Schema: ' . implode(', ', array_keys((array)$firstRecord->values)));
                    
                    // Display first 5 records
                    $recordCount = min(5, count($table->records));
                    for ($i = 0; $i < $recordCount; $i++) {
                        $this->line("Record {$i}: " . json_encode($table->records[$i]->values));
                    }
                    
                    if (count($table->records) > 5) {
                        $this->line("... and " . (count($table->records) - 5) . " more records");
                    }
                }
            }
            
            // Ask to save results to MySQL
            if ($this->confirm('Would you like to save these results to MySQL?', false)) {
                $saved = $this->influxService->queryAndSave($query);
                $this->info('Saved ' . count($saved) . ' records to MySQL');
            }
        } catch (\Exception $e) {
            $this->error('❌ Query execution failed: ' . $e->getMessage());
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
    
    /**
     * Test the full pipeline from InfluxDB to MySQL
     */
    protected function testPipeline()
    {
        $this->info('Testing Full Pipeline (InfluxDB to MySQL)');
        
        $meterId = $this->option('meter_id');
        $period = $this->option('period');
        $date = $this->option('date') ?: date('Y-m-d');
        
        // Verify the meter exists
        if ($this->option('meter_id')) {
            $meter = SmartMeter::where('meter_id', $meterId)->first();
            if (!$meter) {
                $this->warn("Meter ID {$meterId} not found in the database, but continuing with query");
            } else {
                $this->info("Using meter: {$meter->name} ({$meter->meter_id})");
            }
        }
        
        $this->info("Testing getEnergyDashboardData for meter {$meterId}, period {$period}, date {$date}");
        
        try {
            $startTime = microtime(true);
            $dashboardData = $this->influxService->getEnergyDashboardData($meterId, $period, $date);
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->info("Data retrieved in {$duration} seconds");
            
            // Check current data
            $this->checkDataContent($dashboardData['current_data'], 'Current Data');
            
            // Check historical data
            $this->checkDataContent($dashboardData['historical_data'], 'Historical Data');
            
            // Check total data
            $this->info('Total Usage Data:');
            // foreach ($dashboardData['total'] as $totalPeriod => $data) {
            //     $this->line("{$totalPeriod}: Gas: {$data['gas_delivered']} m³, Electricity: {$data['energy_consumed']} kWh, Generation: {$data['energy_produced']} kWh");
            // }
            print_r($dashboardData['total']);
            // Save to MySQL
            if ($this->confirm('Would you like to save this data to MySQL?', true)) {
                $startTime = microtime(true);
                $result = $this->influxService->storeEnergyDashboardData($meterId, $period, $date);
                $duration = round(microtime(true) - $startTime, 2);
                
                if ($result['success']) {
                    $this->info("✅ Data saved to MySQL with ID {$result['id']} in {$duration} seconds");
                    
                    // Verify saved data
                    $savedData = InfluxData::find($result['id']);
                    if ($savedData) {
                        $this->info('✅ Successfully verified saved data');
                        
                        // Show sample of saved data
                        if ($this->option('verbose')) {
                            $this->line('Sample of saved data:');
                            $this->line(json_encode(array_slice($savedData->fields['current_data'], 0, 1), JSON_PRETTY_PRINT));
                        }
                    } else {
                        $this->error('❌ Failed to verify saved data');
                    }
                } else {
                    $this->error('❌ Failed to save data to MySQL');
                }
            }
            
            // Log the success
            Log::info("InfluxDB pipeline test successful for meter {$meterId}, period {$period}, date {$date}");
            
            $this->info('Full pipeline test completed successfully');
        } catch (\Exception $e) {
            $this->error('❌ Pipeline test failed: ' . $e->getMessage());
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            
            // Log the error
            Log::error("InfluxDB pipeline test failed: {$e->getMessage()}", [
                'meter_id' => $meterId,
                'period' => $period,
                'date' => $date,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Check if data content has non-zero values
     */
    protected function checkDataContent(array $data, string $label)
    {
        $this->info("{$label}:");
        
        $nonZeroValues = 0;
        $totalValues = 0;
        
        foreach ($data as $metric => $values) {
            $nonZeroCount = count(array_filter($values, fn($v) => $v != 0));
            $totalCount = count($values);
            
            $nonZeroValues += $nonZeroCount;
            $totalValues += $totalCount;
            
            $this->line("  - {$metric}: {$nonZeroCount}/{$totalCount} non-zero values");
            
            // Display first few non-zero values
            $nonZeroList = array_filter($values, fn($v) => $v != 0);
            $sampleSize = min(5, count($nonZeroList));
            
            if ($sampleSize > 0) {
                $samples = array_slice($nonZeroList, 0, $sampleSize);
                $this->line("    Sample values: " . implode(', ', array_map(fn($v) => round($v, 2), $samples)));
            }
        }
        
        $zeroPercentage = $totalValues > 0 ? round(100 - ($nonZeroValues / $totalValues * 100), 1) : 100;
        
        if ($zeroPercentage == 100) {
            $this->error("❌ All values are zero!");
        } elseif ($zeroPercentage > 90) {
            $this->warn("⚠️ {$zeroPercentage}% of values are zero!");
        } elseif ($zeroPercentage > 50) {
            $this->warn("⚠️ {$zeroPercentage}% of values are zero.");
        } else {
            $this->info("✅ Only {$zeroPercentage}% of values are zero.");
        }
    }
}