<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartMeter extends Model
{
    use HasFactory;

    /**
     * De attributen die massaal toegewezen mogen worden.
     *
     * @var array
     */
    protected $fillable = [
        'meter_id',
        'location',
        'measures_electricity',  // Boolean: kan elektriciteit meten
        'measures_gas',          // Boolean: kan gas meten
        'account_id',
        'installation_date',
        'last_reading_date',
        'active',
        'metadata'
    ];

    /**
     * De attributen die gecast moeten worden.
     *
     * @var array
     */
    protected $casts = [
        'installation_date' => 'datetime',
        'last_reading_date' => 'datetime',
        'active' => 'boolean',
        'measures_electricity' => 'boolean',
        'measures_gas' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Get de gebruiker waartoe deze meter behoort.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'account_id');
    }

    /**
     * Get alle meterstanden van deze slimme meter.
     */
    public function readings()
    {
        return $this->hasMany(MeterReading::class);
    }

    /**
     * Get de laatste meterstand van deze slimme meter.
     */
    public function latestReading()
    {
        return $this->hasOne(MeterReading::class)->latest('timestamp');
    }

    /**
     * Get het huidige elektriciteitsverbruik (laatste meting).
     * 
     * @return float
     */
    public function getCurrentElectricityUsage()
    {
        if (!$this->measures_electricity) return 0;
        
        $reading = $this->latestReading()->first();
        return $reading ? $reading->current_electricity_usage : 0;
    }
    
    /**
     * Get het huidige gasverbruik (laatste meting).
     * 
     * @return float
     */
    public function getCurrentGasUsage()
    {
        if (!$this->measures_gas) return 0;
        
        $reading = $this->latestReading()->first();
        return $reading ? $reading->gas_meter_reading : 0;
    }
    
    /**
     * Get de totale elektriciteit geleverd aan de klant (standaard: tarief 1 + tarief 2).
     * 
     * @return float
     */
    public function getTotalElectricityDelivered()
    {
        if (!$this->measures_electricity) return 0;
        
        $reading = $this->latestReading()->first();
        if (!$reading) {
            return 0;
        }
        
        return ($reading->electricity_delivered_tariff1 ?? 0) + 
               ($reading->electricity_delivered_tariff2 ?? 0);
    }
    
    /**
     * Get de totale elektriciteit teruggeleverd door de klant (standaard: tarief 1 + tarief 2).
     * 
     * @return float
     */
    public function getTotalElectricityReturned()
    {
        if (!$this->measures_electricity) return 0;
        
        $reading = $this->latestReading()->first();
        if (!$reading) {
            return 0;
        }
        
        return ($reading->electricity_returned_tariff1 ?? 0) + 
               ($reading->electricity_returned_tariff2 ?? 0);
    }

    /**
     * Controleer of de smart meter is gekoppeld aan een gebruiker.
     */
    public function isLinked()
    {
        return !is_null($this->account_id);
    }
    
    /**
     * Beschrijf wat deze meter kan meten
     */
    public function getMeasurementTypes()
    {
        $types = [];
        
        if ($this->measures_electricity) {
            $types[] = 'Elektriciteit';
        }
        
        if ($this->measures_gas) {
            $types[] = 'Gas';
        }
        
        return empty($types) ? ['Onbekend'] : $types;
    }
    
    /**
     * Krijg een beschrijvende tekst van wat deze meter meet
     */
    public function getMeasurementTypeString()
    {
        return implode(' & ', $this->getMeasurementTypes());
    }
    
    /**
     * Krijg een lijst met beschikbare metrics voor deze meter.
     * 
     * @return array
     */
    public function getAvailableMetrics()
    {
        $metrics = [];
        
        if ($this->measures_electricity) {
            $metrics = array_merge($metrics, [
                'current_usage' => 'Huidig verbruik (kW)',
                'total_delivered' => 'Totaal geleverd (kWh)',
                'tariff1_delivered' => 'Tarief 1 geleverd (kWh)',
                'tariff2_delivered' => 'Tarief 2 geleverd (kWh)',
                'total_returned' => 'Totaal teruggeleverd (kWh)',
                'tariff1_returned' => 'Tarief 1 teruggeleverd (kWh)',
                'tariff2_returned' => 'Tarief 2 teruggeleverd (kWh)',
            ]);
        }
        
        if ($this->measures_gas) {
            $metrics = array_merge($metrics, [
                'gas_reading' => 'Gasmeterstand (m³)',
            ]);
        }
        
        return $metrics;
    }

    /**
 * Get a display-friendly name for the meter type.
 *
 * @return string
 */
public function getTypeDisplayName()
{
    $types = [];
    
    if ($this->measures_electricity) {
        $types[] = 'Elektriciteit';
    }
    
    if ($this->measures_gas) {
        $types[] = 'Gas';
    }
    
    return empty($types) ? 'Onbekend' : implode(' & ', $types);
}
}