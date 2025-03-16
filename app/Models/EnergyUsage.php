<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyUsage extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'electricity_kwh',
        'gas_m3',
        'reading_type'
    ];

    protected $casts = [
        'date' => 'date',
        'electricity_kwh' => 'float',
        'gas_m3' => 'float'
    ];

    /**
     * Get the user that owns the energy usage.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}