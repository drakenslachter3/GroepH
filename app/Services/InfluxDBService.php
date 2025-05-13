<?php
namespace App\Services;

use App\Models\InfluxData;
use Carbon\Carbon;
use Exception;
use InfluxDB2\Client as InfluxDBClient;

use function Laravel\Prompts\error;
use function Pest\Laravel\call;

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
    public function getDailyEnergyUsage(string $meterId, string $date): array {

        // Tijd start een om 23:00:00 de vorige dag en stopt op 00:00:00 de volgende dag.
        $start = Carbon::createFromFormat('Y-m-d', $date)->subDay()->setTime(23, 0, 0)->toIso8601ZuluString();
        $stop = Carbon::createFromFormat('Y-m-d', $date)->addDay()->startOfDay()->toIso8601ZuluString();

        $query = '
        from(bucket: "' . config('influxdb.bucket') . '")
        |> range(start: ' . $start . ', stop: ' . $stop . ')
        |> filter(fn: (r) => r["signature"] == "' . $meterId . '" and r["_field"] == "energy_consumed" or r["_field"] == "energy_produced" or r["_field"] == "gas_delivered")
        |> aggregateWindow(every: 1h, fn: last, createEmpty: false)
        |> derivative(unit: 1h, nonNegative: true)
        |> pivot(rowKey:["_time"], columnKey:["_field"], valueColumn:"_value")
        |> keep(columns:["_time", "energy_consumed", "energy_produced", "gas_delivered"])
        ';

        try {
            $result = $this->query($query);
            
            $gasUsage              = array_fill(0, 24, 0);
            $electricityUsage      = array_fill(0, 24, 0);
            $electricityGeneration = array_fill(0, 24, 0);

            // Verwerk de resultaten
            if (!empty($result) && isset($result[0]->records)) {
                foreach ($result[0]->records as $record) {

                    // Er wordt 1 afgetrokken omdat de tijd start de vorige dat om 23:00:00 i.v.m. de derivative in de influx query
                    $hour = (int) Carbon::parse($record->values['_time'])->format('G');
                    
                    // Verwerk de gegevens
                    if (isset($record->values['energy_consumed'])) {
                        $gasUsage[$hour] = (float) $record->values['energy_consumed'];
                    }

                    if (isset($record->values['energy_produced'])) {
                        $electricityUsage[$hour] = (float) $record->values['energy_produced'];
                    }

                    if (isset($record->values['gas_delivered'])) {
                        $electricityGeneration[$hour] = (float) $record->values['gas_delivered'];
                    }
                }
            }

            return [
                'gas_usage'              => $gasUsage,
                'electricity_usage'      => $electricityUsage,
                'electricity_generation' => $electricityGeneration,
            ];
        }
        catch(Exception $e){
            dd('Error: ' . $e->getMessage());
            return [];
        }
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

        $startDate = Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-01")->subDay()->toIso8601ZuluString();
        $endDate = Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-01")
        ->addMonth()
        ->startOfDay()
        ->toIso8601ZuluString();

        $query = '
        from(bucket: "' . config('influxdb.bucket') . '")
        |> range(start: time(v: "' . $startDate . '"), stop: time(v: "' . $endDate . '"))
        |> filter(fn: (r) => r["signature"] == "' . $meterId . '" and (r["_field"] == "energy_consumed" or r["_field"] == "energy_produced" or r["_field"] == "gas_delivered"))
        |> aggregateWindow(every: 1d, fn: last, createEmpty: false)
        |> derivative(unit: 1d, nonNegative: true)
        |> pivot(rowKey:["_time"], columnKey:["_field"], valueColumn:"_value")
        |> keep(columns:["_time", "energy_consumed", "energy_produced", "gas_delivered"])
        ';

        $result = $this->query($query);

        // Initialiseer arrays voor alle dagen van de maand
        $gasUsage              = array_fill(0, $daysInMonth, 0);
        $electricityUsage      = array_fill(0, $daysInMonth, 0);
        $electricityGeneration = array_fill(0, $daysInMonth, 0);

        // Verwerk resultaten
        if (! empty($result) && isset($result[0]->records)) {
            foreach ($result[0]->records as $record) {
                $day = (int) date('j', strtotime($record->values['_time'])) - 1; // 0-based index

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
        // Zet de startdatum op 1 december van het vorige jaar, zodat de derivative functie in de influx query data heeft om mee te vergelijken
        $startDate = ($year - 1) . "-12-01T00:00:00Z"; 
        $endDate   = "{$year}-12-31T23:59:59Z";

        $query = '
        from(bucket: "' . config('influxdb.bucket') . '")
        |> range(start: time(v: "' . $startDate . '"), stop: time(v: "' . $endDate . '"))
        |> filter(fn: (r) => r["signature"] == "' . $meterId . '" and (r["_field"] == "energy_consumed" or r["_field"] == "energy_produced" or r["_field"] == "gas_delivered"))
        |> aggregateWindow(every: 1mo, fn: last, createEmpty: false)
        |> derivative(unit: 1mo, nonNegative: true)
        |> pivot(rowKey:["_time"], columnKey:["_field"], valueColumn:"_value")
        |> keep(columns:["_time", "energy_consumed", "energy_produced", "gas_delivered"])
        ';
    
        $result = $this->query($query);

        // Initialiseer arrays voor alle maanden (0-11)
        $gasUsage              = array_fill(0, 12, 0);
        $electricityUsage      = array_fill(0, 12, 0);
        $electricityGeneration = array_fill(0, 12, 0);

        // Verwerk resultaten
        if (! empty($result) && isset($result[0]->records)) {
            foreach ($result[0]->records as $record) {

                /* Hier wordt 2 afgetrokken
                   één omdat de array een 0 based index heeft
                   de andere omdat de startdatum 1 december van het vorige jaar is, i.v.m.
                   de derivative functie in de influx query.
                   De tweede aftrek is dus nodig om het jaar inplaats van in december te laten
                   beginnen in januari!
                */
                
                $month = (int) date('n', strtotime($record->values['_time'])) - 2;

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
