<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnergyBudget extends Model
{
    protected $fillable = [
        'gas_target_m3',
        'gas_target_euro',
        'electricity_target_kwh',
        'electricity_target_euro',
        'year'
    ];
}

