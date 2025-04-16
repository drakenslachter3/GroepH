<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyEnergyBudget extends Model
{
    protected $fillable = [
        'user_id',
        'energy_budget_id',
        'month',
        'gas_target_m3',
        'electricity_target_kwh',
        'is_default',
    ];

    protected $casts = [
        'gas_target_m3' => 'float',
        'electricity_target_kwh' => 'float',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function energyBudget(): BelongsTo
    {
        return $this->belongsTo(EnergyBudget::class);
    }
}