<?php

namespace App\Console\Commands;

use App\Services\InfluxDBService;
use Illuminate\Console\Command;

class TestInfluxDBConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'influxdb:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to InfluxDB and execute a sample query';

    /**
     * Execute the console command.
     */
    public function handle(InfluxDBService $influxService)
    {
        $this->info('Testing connection to InfluxDB...');

        if ($influxService->testConnection()) {
            $this->info('✅ Connection successful!');
            
            // Get bucket name from config
            $bucket = config('influxdb.bucket');
            
            // Prompt for a simple query
            $this->info('Now testing a simple query...');
            
            // Example of a simple query using Flux
            $query = <<<EOD
from(bucket: "{$bucket}")
  |> range(start: -1h)
  |> limit(n: 10)
EOD;

            try {
                $results = $influxService->query($query);
                
                if (empty($results)) {
                    $this->warn('Query executed successfully but returned no data.');
                } else {
                    $this->info('Query executed successfully!');
                    $this->info('Retrieved ' . count($results) . ' tables.');
                    
                    // Display first few records for preview
                    $firstTable = $results[0] ?? null;
                    if ($firstTable && isset($firstTable->records)) {
                        $this->info('First few records:');
                        foreach (array_slice($firstTable->records, 0, 3) as $index => $record) {
                            $this->line("Record " . ($index + 1) . ": " . json_encode($record->values));
                        }
                        
                        // Ask if user wants to save to MySQL
                        if ($this->confirm('Would you like to save these results to MySQL?', false)) {
                            $saved = $influxService->queryAndSave($query);
                            $this->info('Saved ' . count($saved) . ' records to MySQL.');
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error('Error executing query: ' . $e->getMessage());
            }
        } else {
            $this->error('❌ Failed to connect to InfluxDB. Please check your configuration.');
        }
    }
}