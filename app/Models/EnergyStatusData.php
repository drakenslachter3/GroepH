<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnergyStatusData extends Model
{
    use HasFactory;

    protected $fillable = [
        'meter_id',
        'period',
        'date',
        'electricity_usage',
        'electricity_target',
        'electricity_cost',
        'electricity_percentage',
        'electricity_status',
        'electricity_previous_year',
        'gas_usage',
        'gas_target',
        'gas_cost',
        'gas_percentage',
        'gas_status',
        'gas_previous_year',
        'last_updated'
    ];

    protected $casts = [
        'electricity_usage' => 'float',
        'electricity_target' => 'float',
        'electricity_cost' => 'float',
        'electricity_percentage' => 'float',
        'electricity_previous_year' => 'json',
        'gas_usage' => 'float',
        'gas_target' => 'float',
        'gas_cost' => 'float',
        'gas_percentage' => 'float',
        'gas_previous_year' => 'json',
        'last_updated' => 'datetime',
    ];
}