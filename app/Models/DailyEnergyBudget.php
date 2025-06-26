<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyEnergyBudget extends Model
{
    protected $fillable = [
        'user_id',
        'monthly_energy_budget_id',
        'day',
        'gas_target_m3',
        'electricity_target_kwh',
    ];

    protected $casts = [
        'gas_target_m3' => 'float',
        'electricity_target_kwh' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function monthlyBudget(): BelongsTo
    {
        return $this->belongsTo(MonthlyEnergyBudget::class, 'monthly_energy_budget_id');
    }
}