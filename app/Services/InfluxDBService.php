<?php

namespace App\Services;

use App\Models\InfluxData;
use InfluxDB2\Client as InfluxDBClient;
use Carbon\Carbon;

class InfluxDBService
{
    protected $client;

    public function __construct(InfluxDBClient $client)
    {
        $this->client = $client;
    }

    /**
     * Query InfluxDB and save results to MySQL
     *
     * @param string $query
     * @return array
     */
    public function queryAndSave(string $query): array
    {
        $queryApi = $this->client->createQueryApi();
        $result = $queryApi->query($query);
        
        $savedData = [];
        
        // Handle different result structures
        if (is_array($result) && !empty($result)) {
            // Process each table in the result
            foreach ($result as $table) {
                if (!isset($table->records) || !is_array($table->records)) {
                    continue;
                }
                
                foreach ($table->records as $record) {
                $measurement = $record->values['_measurement'] ?? 'unknown';
                $fields = [];
                $tags = [];
                
                // Extract field values
                foreach ($record->values as $key => $value) {
                    if (!in_array($key, ['_time', '_measurement', '_start', '_stop'])) {
                        // Check if it's a tag or field
                        if (strpos($key, '_') === 0) {
                            $tagKey = substr($key, 1); // Remove the leading underscore
                            $tags[$tagKey] = $value;
                        } else {
                            $fields[$key] = $value;
                        }
                    }
                }
                
                // Extract time
                $time = Carbon::parse($record->values['_time'] ?? now());
                
                // Save to MySQL
                $influxData = InfluxData::create([
                    'measurement' => $measurement,
                    'tags' => $tags,
                    'fields' => $fields,
                    'time' => $time,
                ]);
                
                    $savedData[] = $influxData;
                }
            }
        }
        
        return $savedData;
    }

    /**
     * Test the connection to InfluxDB
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $health = $this->client->health();
            return $health->getStatus() === 'pass';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get data from InfluxDB without saving
     *
     * @param string $query
     * @return array
     */
    public function query(string $query): array
    {
        $queryApi = $this->client->createQueryApi();
        return $queryApi->query($query);
    }
}