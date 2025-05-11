<?php
namespace App\Services;

use App\Models\InfluxData;
use Carbon\Carbon;
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
        // Flux query voor energieverbruik per uur
        $query = "
from(bucket: \"" . config('influxdb.bucket') . "\")
  |> range(start: {$date}T00:00:00Z, stop: {$date}T23:59:59Z)
  |> filter(fn: (r) => r._measurement == \"energy_usage\" and r.meter_id == \"{$meterId}\")
  |> aggregateWindow(every: 1h, fn: mean, createEmpty: true)
  |> pivot(rowKey:[\"_time\"], columnKey: [\"_field\"], valueColumn: \"_value\")
  |> keep(columns: [\"_time\", \"gas_usage\", \"electricity_usage\", \"electricity_generation\"])
";

        $result = $this->query($query);

        // Initialiseer arrays voor 24 uur (0-23)
        $hours                 = range(0, 23);
        $gasUsage              = array_fill(0, 24, 0);
        $electricityUsage      = array_fill(0, 24, 0);
        $electricityGeneration = array_fill(0, 24, 0);

        // Verwerk resultaten
        if (! empty($result) && isset($result[0]->records)) {
            foreach ($result[0]->records as $record) {
                $hour = (int) date('G', strtotime($record->values['_time']));

                if (isset($record->values['gas_usage'])) {
                    $gasUsage[$hour] = (float) $record->values['gas_usage'];
                }

                if (isset($record->values['electricity_usage'])) {
                    $electricityUsage[$hour] = (float) $record->values['electricity_usage'];
                }

                if (isset($record->values['electricity_generation'])) {
                    $electricityGeneration[$hour] = (float) $record->values['electricity_generation'];
                }
            }
        }

        return [
            'gas_usage'              => $gasUsage,
            'electricity_usage'      => $electricityUsage,
            'electricity_generation' => $electricityGeneration,
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
        $daysInMonth        = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // Flux query voor energieverbruik per dag
        $startDate = "{$yearMonth}-01T00:00:00Z";
        $endDate   = "{$yearMonth}-{$daysInMonth}T23:59:59Z";

        $query = "
from(bucket: \"" . config('influxdb.bucket') . "\")
  |> range(start: {$startDate}, stop: {$endDate})
  |> filter(fn: (r) => r._measurement == \"energy_usage\" and r.meter_id == \"{$meterId}\")
  |> aggregateWindow(every: 1d, fn: sum, createEmpty: true)
  |> pivot(rowKey:[\"_time\"], columnKey: [\"_field\"], valueColumn: \"_value\")
  |> keep(columns: [\"_time\", \"gas_usage\", \"electricity_usage\", \"electricity_generation\"])
";

        $result = $this->query($query);

        // Initialiseer arrays voor alle dagen van de maand
        $gasUsage              = array_fill(0, $daysInMonth, 0);
        $electricityUsage      = array_fill(0, $daysInMonth, 0);
        $electricityGeneration = array_fill(0, $daysInMonth, 0);

        // Verwerk resultaten
        if (! empty($result) && isset($result[0]->records)) {
            foreach ($result[0]->records as $record) {
                $day = (int) date('j', strtotime($record->values['_time'])) - 1; // 0-based index

                if (isset($record->values['gas_usage'])) {
                    $gasUsage[$day] = (float) $record->values['gas_usage'];
                }

                if (isset($record->values['electricity_usage'])) {
                    $electricityUsage[$day] = (float) $record->values['electricity_usage'];
                }

                if (isset($record->values['electricity_generation'])) {
                    $electricityGeneration[$day] = (float) $record->values['electricity_generation'];
                }
            }
        }

        return [
            'gas_usage'              => $gasUsage,
            'electricity_usage'      => $electricityUsage,
            'electricity_generation' => $electricityGeneration,
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
        // Flux query voor energieverbruik per maand
        $startDate = "{$year}-01-01T00:00:00Z";
        $endDate   = "{$year}-12-31T23:59:59Z";

        $query = "
from(bucket: \"" . config('influxdb.bucket') . "\")
  |> range(start: {$startDate}, stop: {$endDate})
  |> filter(fn: (r) => r._measurement == \"energy_usage\" and r.meter_id == \"{$meterId}\")
  |> aggregateWindow(every: 1mo, fn: sum, createEmpty: true)
  |> pivot(rowKey:[\"_time\"], columnKey: [\"_field\"], valueColumn: \"_value\")
  |> keep(columns: [\"_time\", \"gas_usage\", \"electricity_usage\", \"electricity_generation\"])
";

        $result = $this->query($query);

        // Initialiseer arrays voor alle maanden (0-11)
        $gasUsage              = array_fill(0, 12, 0);
        $electricityUsage      = array_fill(0, 12, 0);
        $electricityGeneration = array_fill(0, 12, 0);

        // Verwerk resultaten
        if (! empty($result) && isset($result[0]->records)) {
            foreach ($result[0]->records as $record) {
                $month = (int) date('n', strtotime($record->values['_time'])) - 1; // 0-based index

                if (isset($record->values['gas_usage'])) {
                    $gasUsage[$month] = (float) $record->values['gas_usage'];
                }

                if (isset($record->values['electricity_usage'])) {
                    $electricityUsage[$month] = (float) $record->values['electricity_usage'];
                }

                if (isset($record->values['electricity_generation'])) {
                    $electricityGeneration[$month] = (float) $record->values['electricity_generation'];
                }
            }
        }

        return [
            'gas_usage'              => $gasUsage,
            'electricity_usage'      => $electricityUsage,
            'electricity_generation' => $electricityGeneration,
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

    /**
     * Haal totale energieverbruik op voor de afgelopen periode
     *
     * @param string $meterId De ID van de slimme meter
     * @param string $period Periode ('week', 'month', 'year')
     * @return array
     */
    public function getTotalEnergyUsage(string $meterId, string $period): array
    {
        $now = date('Y-m-d\TH:i:s\Z');

        switch ($period) {
            case 'week':
                $startDate = date('Y-m-d\TH:i:s\Z', strtotime('-1 week'));
                break;
            case 'month':
                $startDate = date('Y-m-d\TH:i:s\Z', strtotime('-1 month'));
                break;
            case 'year':
                $startDate = date('Y-m-d\TH:i:s\Z', strtotime('-1 year'));
                break;
            default:
                throw new \InvalidArgumentException("Ongeldige periode: {$period}");
        }

        $query = "
from(bucket: \"" . config('influxdb.bucket') . "\")
  |> range(start: {$startDate}, stop: {$now})
  |> filter(fn: (r) => r._measurement == \"energy_usage\" and r.meter_id == \"{$meterId}\")
  |> sum()
  |> pivot(rowKey:[\"_measurement\"], columnKey: [\"_field\"], valueColumn: \"_value\")
  |> keep(columns: [\"gas_usage\", \"electricity_usage\", \"electricity_generation\"])
";

        $result = $this->query($query);

        $gasUsage              = 0;
        $electricityUsage      = 0;
        $electricityGeneration = 0;

        // Verwerk resultaten
        if (! empty($result) && isset($result[0]->records) && ! empty($result[0]->records)) {
            $record = $result[0]->records[0];

            if (isset($record->values['gas_usage'])) {
                $gasUsage = (float) $record->values['gas_usage'];
            }

            if (isset($record->values['electricity_usage'])) {
                $electricityUsage = (float) $record->values['electricity_usage'];
            }

            if (isset($record->values['electricity_generation'])) {
                $electricityGeneration = (float) $record->values['electricity_generation'];
            }
        }

        return [
            'gas_usage'              => $gasUsage,
            'electricity_usage'      => $electricityUsage,
            'electricity_generation' => $electricityGeneration,
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
                'week'  => $weekTotal,
                'month' => $monthTotal,
                'year'  => $yearTotal,
            ],
        ];
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
