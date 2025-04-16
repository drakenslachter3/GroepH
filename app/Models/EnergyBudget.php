<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnergyBudget extends Model
{
    protected $fillable = [
        'user_id',
        'gas_target_m3',
        'gas_target_euro',
        'electricity_target_kwh',
        'electricity_target_euro',
        'year',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function monthlyBudgets(): HasMany
    {
        return $this->hasMany(MonthlyEnergyBudget::class);
    }
}