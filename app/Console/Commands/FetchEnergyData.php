<?php

namespace App\Console\Commands;

use App\Models\SmartMeter;
use App\Services\InfluxDBService;
use Illuminate\Console\Command;

class FetchEnergyData extends Command
{
    protected $signature = 'energy:fetch {--period=day} {--date=} {--meter=}';
    protected $description = 'Haal energiegegevens op uit InfluxDB en sla ze op in MySQL';

    public function handle(InfluxDBService $influxService)
    {
        $period = $this->option('period') ?: 'day';
        $date = $this->option('date') ?: date('Y-m-d');
        $meterId = $this->option('meter');
        
        $smartMeters = $meterId
            ? SmartMeter::where('meter_id', $meterId)->get()
            : SmartMeter::where('active', true)->get();
            
        if ($smartMeters->isEmpty()) {
            $this->error('Geen actieve slimme meters gevonden.');
            return 1;
        }
        
        $this->info("Energiegegevens ophalen voor periode: {$period}, datum: {$date}");
        $bar = $this->output->createProgressBar($smartMeters->count());
        
        foreach ($smartMeters as $meter) {
            try {
                $result = $influxService->storeEnergyDashboardData(
                    $meter->meter_id,
                    $period,
                    $date
                );
                
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Fout bij meter {$meter->meter_id}: " . $e->getMessage());
            }
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Klaar!');
        
        return 0;
    }
}