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
        'type',
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
        'metadata' => 'array'
    ];

    /**
     * Get het account waartoe deze meter behoort.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
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
        return $this->hasOne(MeterReading::class)->latest();
    }

    /**
     * Controleer of de smart meter is gekoppeld aan een account.
     */
    public function isLinked()
    {
        return !is_null($this->account_id);
    }
}