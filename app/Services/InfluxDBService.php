<?php
namespace App\Services;

use App\Models\InfluxData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InfluxDB2\Client as InfluxDBClient;

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
        $result   = $queryApi->query($query);

        $savedData = [];

        // Handle different result structures
        if (is_array($result) && ! empty($result)) {
            // Process each table in the result
            foreach ($result as $table) {
                if (! isset($table->records) || ! is_array($table->records)) {
                    continue;
                }

                foreach ($table->records as $record) {
                    $measurement = $record->values['_measurement'] ?? 'unknown';
                    $fields      = [];
                    $tags        = [];

                    // Extract field values
                    foreach ($record->values as $key => $value) {
                        if (! in_array($key, ['_time', '_measurement', '_start', '_stop'])) {
                            // Check if it's a tag or field
                            if (strpos($key, '_') === 0) {
                                $tagKey        = substr($key, 1); // Remove the leading underscore
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
                        'tags'        => $tags,
                        'fields'      => $fields,
                        'time'        => $time,
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

    /**
     * Haal energieverbruik per uur op voor een specifieke dag
     *
     * @param string $meterId De ID van de slimme meter
     * @param string $date De dag in formaat 'YYYY-MM-DD'
     * @return array
     */
    public function getDailyEnergyUsage(string $meterId, string $date): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $date, 'UTC')->startOfDay()->toIso8601ZuluString();
        $stop = Carbon::createFromFormat('Y-m-d', $date, 'UTC')->endOfDay()->toIso8601ZuluString();

        $query = '
        from(bucket: "' . config('influxdb.bucket') . '")
        |> range(start: ' . $start . ', stop: ' . $stop . ')
        |> filter(fn: (r) => r["signature"] == "' . $meterId . '")
        |> filter(fn: (r) => r["_field"] == "gas_delivered" or r["_field"] == "energy_consumed" or r["_field"] == "energy_produced")
        |> filter(fn: (r) => r["_measurement"] == "dsmr")
        |> aggregateWindow(every: 1h, fn: last, createEmpty: true)
        |> derivative(unit: 1h, nonNegative: true)
        |> pivot(rowKey:["_time"], columnKey:["_field"], valueColumn:"_value")
        |> keep(columns:["_time", "energy_consumed", "energy_produced", "gas_delivered"])
        |> timeShift(duration: -1h)
        ';

        $result = $this->query($query);

        $gasUsage              = array_fill(0, 24, 0);
        $electricityUsage      = array_fill(0, 24, 0);
        $electricityGeneration = array_fill(0, 24, 0);

        if (!empty($result) && isset($result[0]->records)) {
            foreach ($result[0]->records as $record) {
                if (isset($record->values['_time'])) {
                    $hour = (int) date('G', strtotime($record->values['_time']));

                    if (isset($record->values['gas_delivered'])) {
                        $gasUsage[$hour] = (float) $record->values['gas_delivered'];
                    }
                    if (isset($record->values['energy_consumed'])) {
                        $electricityUsage[$hour] = (float) $record->values['energy_consumed'];
                    }
                    if (isset($record->values['energy_produced'])) {
                        $electricityGeneration[$hour] = (float) $record->values['energy_produced'];
                    }
                }
            }
        }

        return [
            'gas_delivered'   => $gasUsage,
            'energy_consumed' => $electricityUsage,
            'energy_produced' => $electricityGeneration,
        ];
    }

    /**
     * Haal energieverbruik per dag op voor een specifieke maand
     *
     * @param string $meterId De ID van de slimme meter
     * @param string $yearMonth De maand in formaat 'YYYY-MM'
     * @return array
     */
    public function getMonthlyEnergyUsage(string $meterId, string $yearMonth): array
    {
        list($year, $month) = explode('-', $yearMonth);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);

        $startDate = Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-01")->startOfDay()->toIso8601ZuluString();
        $endDate = Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-{$daysInMonth}")->endOfDay()->toIso8601ZuluString();

        $query = '
        from(bucket: "' . config('influxdb.bucket') . '")
        |> range(start: ' . $startDate . ', stop: ' . $endDate . ')
        |> filter(fn: (r) => r["signature"] == "' . $meterId . '")
        |> filter(fn: (r) => r["_field"] == "gas_delivered" or r["_field"] == "energy_consumed" or r["_field"] == "energy_produced")
        |> aggregateWindow(every: 1d, fn: last, createEmpty: true)
        |> derivative(unit: 1d, nonNegative: true)
        |> pivot(rowKey:["_time"], columnKey:["_field"], valueColumn:"_value")
        |> keep(columns:["_time", "energy_consumed", "energy_produced", "gas_delivered"])
        |> timeShift(duration: -1d)
        ';

        $result = $this->query($query);

        $gasUsage = array_fill(0, $daysInMonth, 0);
        $electricityUsage = array_fill(0, $daysInMonth, 0);
        $electricityGeneration = array_fill(0, $daysInMonth, 0);

        if (!empty($result) && isset($result[0]->records)) {
            foreach ($result[0]->records as $record) {
                if (isset($record->values['_time'])) {
                    $day = (int) date('j', strtotime($record->values['_time'])) - 1;

                    if (isset($record->values['gas_delivered'])) {
                        $gasUsage[$day] = (float) $record->values['gas_delivered'];
                    }
                    if (isset($record->values['energy_consumed'])) {
                        $electricityUsage[$day] = (float) $record->values['energy_consumed'];
                    }
                    if (isset($record->values['energy_produced'])) {
                        $electricityGeneration[$day] = (float) $record->values['energy_produced'];
                    }
                }
            }
        }

        return [
            'gas_delivered'   => $gasUsage,
            'energy_consumed' => $electricityUsage,
            'energy_produced' => $electricityGeneration,
        ];
    }

    /**
     * Haal energieverbruik per maand op voor een specifiek jaar
     *
     * @param string $meterId De ID van de slimme meter
     * @param string $year Het jaar in formaat 'YYYY'
     * @return array
     */
    public function getYearlyEnergyUsage(string $meterId, string $year): array
    {
        $startDate = Carbon::createFromFormat('Y-m-d', "{$year}-01-01")->startOfDay()->toIso8601ZuluString();;
        $endDate = Carbon::createFromFormat('Y-m-d', "{$year}-12-31")->endOfDay()->toIso8601ZuluString();

        $query = '
        from(bucket: "' . config('influxdb.bucket') . '")
        |> range(start: ' . $startDate . ', stop: ' . $endDate . ')
        |> filter(fn: (r) => r["signature"] == "' . $meterId . '")
        |> filter(fn: (r) => r["_field"] == "gas_delivered" or r["_field"] == "energy_consumed" or r["_field"] == "energy_produced")
        |> aggregateWindow(every: 1mo, fn: last, createEmpty: false)
        |> derivative(unit: 1mo, nonNegative: true)
        |> pivot(rowKey:["_time"], columnKey:["_field"], valueColumn:"_value")
        |> keep(columns:["_time", "energy_consumed", "energy_produced", "gas_delivered"])
        |> timeShift(duration: -1mo)
        ';

        $result = $this->query($query);

        $gasUsage              = array_fill(0, 12, 0);
        $electricityUsage      = array_fill(0, 12, 0);
        $electricityGeneration = array_fill(0, 12, 0);

        if (!empty($result) && isset($result[0]->records)) {
            foreach ($result[0]->records as $record) {
                if (isset($record->values['_time'])) {
                    $month = (int) date('n', strtotime($record->values['_time'])) - 1;

                    if (isset($record->values['gas_delivered'])) {
                        $gasUsage[$month] = (float) $record->values['gas_delivered'];
                    }
                    if (isset($record->values['energy_consumed'])) {
                        $electricityUsage[$month] = (float) $record->values['energy_consumed'];
                    }
                    if (isset($record->values['energy_produced'])) {
                        $electricityGeneration[$month] = (float) $record->values['energy_produced'];
                    }
                }
            }
        }

        return [
            'gas_delivered'   => $gasUsage,
            'energy_consumed' => $electricityUsage,
            'energy_produced' => $electricityGeneration,
        ];
    }

/**
 * Haal historische gegevens op voor vergelijking (vorig jaar)
 *
 * @param string $meterId De ID van de slimme meter
 * @param string $period Periode ('day', 'month', 'year')
 * @param string $date De referentiedatum
 * @return array
 */
    public function getHistoricalComparison(string $meterId, string $period, string $date): array
    {
        switch ($period) {
            case 'day':
                $previousDate = date('Y-m-d', strtotime($date . ' -1 year'));
                return $this->getDailyEnergyUsage($meterId, $previousDate);

            case 'month':
                list($year, $month) = explode('-', $date);
                $previousYearMonth  = ($year - 1) . '-' . $month;
                return $this->getMonthlyEnergyUsage($meterId, $previousYearMonth);

            case 'year':
                $previousYear = (int) $date - 1;
                return $this->getYearlyEnergyUsage($meterId, $previousYear);

            default:
                throw new \InvalidArgumentException("Ongeldige periode: {$period}");
        }
    }

    public function getTotalEnergyUsage(string $meterId, string $period): array
    {
        // Determine time range based on period
        $now = $this->formatDateForFlux(date('Y-m-d H:i:s'));

        switch ($period) {
            case 'week':
                $startDate = $this->formatDateForFlux(date('Y-m-d H:i:s', strtotime('-1 week')));
                break;
            case 'month':
                $startDate = $this->formatDateForFlux(date('Y-m-d H:i:s', strtotime('-1 month')));
                break;
            case 'year':
                $startDate = $this->formatDateForFlux(date('Y-m-d H:i:s', strtotime('-1 year')));
                break;
            default:
                throw new \InvalidArgumentException("Ongeldige periode: {$period}");
        }

        // Get first reading in the period
        $firstQuery = "
        from(bucket: \"" . config('influxdb.bucket') . "\")
        |> range(start: {$startDate}, stop: {$now})
        |> filter(fn: (r) => r._measurement == \"dsmr\" and r.signature == \"{$meterId}\")
        |> first()
        |> pivot(rowKey:[\"_measurement\"], columnKey: [\"_field\"], valueColumn: \"_value\")
        ";

        // Get last reading in the period
        $lastQuery = "
        from(bucket: \"" . config('influxdb.bucket') . "\")
        |> range(start: {$startDate}, stop: {$now})
        |> filter(fn: (r) => r._measurement == \"dsmr\" and r.signature == \"{$meterId}\")
        |> last()
        |> pivot(rowKey:[\"_measurement\"], columnKey: [\"_field\"], valueColumn: \"_value\")
        ";

        // Execute the queries
        $firstResult = $this->query($firstQuery);
        $lastResult  = $this->query($lastQuery);

        // Log debug info
        Log::debug("First reading query: {$firstQuery}");
        Log::debug("Last reading query: {$lastQuery}");

        // Initialize values
        $firstGas         = 0;
        $lastGas          = 0;
        $firstElectricity = 0;
        $lastElectricity  = 0;
        $firstGeneration  = 0;
        $lastGeneration   = 0;

        // Process first reading
        if (!empty($firstResult) && isset($firstResult[0]->records) && !empty($firstResult[0]->records)) {
            $record = $firstResult[0]->records[0];

            if (isset($record->values['gas_delivered'])) {
                $firstGas = (float) $record->values['gas_delivered'];
            }

            if (isset($record->values['energy_consumed'])) {
                $firstElectricity = (float) $record->values['energy_consumed'];
            }

            if (isset($record->values['energy_produced'])) {
                $firstGeneration = (float) $record->values['energy_produced'];
            }
        }

        // Process last reading
        if (!empty($lastResult) && isset($lastResult[0]->records) && !empty($lastResult[0]->records)) {
            $record = $lastResult[0]->records[0];

            if (isset($record->values['gas_delivered'])) {
                $lastGas = (float) $record->values['gas_delivered'];
            }

            if (isset($record->values['energy_consumed'])) {
                $lastElectricity = (float) $record->values['energy_consumed'];
            }

            if (isset($record->values['energy_produced'])) {
                $lastGeneration = (float) $record->values['energy_produced'];
            }
        }

        // Calculate differences (consumption over the period)
        $gasUsage              = $lastGas - $firstGas;
        $electricityUsage      = $lastElectricity - $firstElectricity;
        $electricityGeneration = $lastGeneration - $firstGeneration;

        // Ensure we don't return negative values
        $gasUsage              = max(0, $gasUsage);
        $electricityUsage      = max(0, $electricityUsage);
        $electricityGeneration = max(0, $electricityGeneration);

        return [
            'gas_delivered'   => $gasUsage,
            'energy_consumed' => $electricityUsage,
            'energy_produced' => $electricityGeneration,
        ];
    }

/**
 * Haal alle benodigde energiegegevens op voor het dashboard
 *
 * @param string $meterId De ID van de slimme meter
 * @param string $period Periode ('day', 'month', 'year')
 * @param string $date De referentiedatum
 * @return array
 */
    public function getEnergyDashboardData(string $meterId, string $period, string $date): array
    {
        // Haal huidige gegevens op
        $currentData = [];
        switch ($period) {
            case 'day':
                $currentData = $this->getDailyEnergyUsage($meterId, $date);
                break;
            case 'month':
                $currentData = $this->getMonthlyEnergyUsage($meterId, $date);
                break;
            case 'year':
                $currentData = $this->getYearlyEnergyUsage($meterId, $date);
                break;
        }

        // Haal historische gegevens op voor vergelijking
        $historicalData = $this->getHistoricalComparison($meterId, $period, $date);

        // Haal totale verbruiksgegevens op
        $weekTotal  = $this->getTotalEnergyUsage($meterId, 'week');
        $monthTotal = $this->getTotalEnergyUsage($meterId, 'month');
        $yearTotal  = $this->getTotalEnergyUsage($meterId, 'year');

        return [
            'current_data'    => $currentData,
            'historical_data' => $historicalData,
            'total'           => [
                'week'  => [
                    'gas_usage'              => $weekTotal['gas_delivered'],
                    'electricity_usage'      => $weekTotal['energy_consumed'],
                    'electricity_generation' => $weekTotal['energy_produced'],
                ],
                'month' => [
                    'gas_usage'              => $monthTotal['gas_delivered'],
                    'electricity_usage'      => $monthTotal['energy_consumed'],
                    'electricity_generation' => $monthTotal['energy_produced'],
                ],
                'year'  => [
                    'gas_usage'              => $yearTotal['gas_delivered'],
                    'electricity_usage'      => $yearTotal['energy_consumed'],
                    'electricity_generation' => $yearTotal['energy_produced'],
                ],
            ],
        ];
    }
    private function formatDateForFlux(string $dateString): string
    {
        // Parse the date string to a timestamp
        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            // Log error and fall back to current time
            Log::error("Invalid date string for Flux query: {$dateString}");
            $timestamp = time();
        }

        // Format date in RFC3339 format which is accepted by Flux
        return date('Y-m-d\TH:i:s\Z', $timestamp);
    }
    public function storeEnergyData(Request $request)
    {
        $validated = $request->validate([
            'meter_id' => 'required|string|exists:smart_meters,meter_id',
            'period'   => 'required|string|in:day,month,year',
            'date'     => 'required|date_format:Y-m-d',
        ]);

        try {
            $result = $this->influxService->storeEnergyDashboardData(
                $validated['meter_id'],
                $validated['period'],
                $validated['date']
            );

            return redirect()->route('dashboard')
                ->with('success', 'Energiegegevens succesvol opgeslagen.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Fout bij opslaan energiegegevens: ' . $e->getMessage());
        }
    }
    /**
     * Haal energiegegevens op en sla ze op in de MySQL database
     *
     * @param string $meterId De ID van de slimme meter
     * @param string $period Periode ('day', 'month', 'year')
     * @param string $date De referentiedatum
     * @return array
     */
    public function storeEnergyDashboardData(string $meterId, string $period, string $date): array
    {
        // Haal data op
        $dashboardData = $this->getEnergyDashboardData($meterId, $period, $date);

        // Sla huidige gegevens op
        $influxData = \App\Models\InfluxData::create([
            'measurement' => 'energy_dashboard',
            'tags'        => [
                'meter_id' => $meterId,
                'period'   => $period,
                'date'     => $date,
            ],
            'fields'      => [
                'current_data'    => $dashboardData['current_data'],
                'historical_data' => $dashboardData['historical_data'],
                'total'           => $dashboardData['total'],
            ],
            'time'        => now(),
        ]);

        return [
            'success' => true,
            'id'      => $influxData->id,
            'data'    => $dashboardData,
        ];
    }
}
