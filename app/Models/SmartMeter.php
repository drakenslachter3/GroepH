<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class SmartMeter extends Model
{
    use HasFactory;

    protected $fillable = [
        'meter_id',
        'name',
        'location',
        'measures_electricity',
        'measures_gas',
        'installation_date',
        'active',
        'account_id',
        'last_reading_date',
    ];

    protected $casts = [
        'measures_electricity' => 'boolean',
        'measures_gas' => 'boolean',
        'active' => 'boolean',
        'installation_date' => 'date',
        'last_reading_date' => 'datetime',
    ];

    /**
     * Relationship with User (account owner)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_id');
    }

    /**
     * Relationship with MeterReading
     */
    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    /**
     * Relationship with EnergyBudget
     */
    public function energyBudgets(): HasMany
    {
        return $this->hasMany(EnergyBudget::class);
    }

    /**
     * Get the current year's budget for this meter
     */
    public function getCurrentBudget(): ?EnergyBudget
    {
        return $this->energyBudgets()
            ->where('year', date('Y'))
            ->with(['monthlyBudgets' => function($query) {
                $query->orderBy('month');
            }])
            ->first();
    }

    /**
     * Get budget for a specific year
     */
    public function getBudgetForYear(int $year): ?EnergyBudget
    {
        return $this->energyBudgets()
            ->where('year', $year)
            ->with(['monthlyBudgets' => function($query) {
                $query->orderBy('month');
            }])
            ->first();
    }

    /**
     * Check if this meter has a budget set for the current year
     */
    public function hasBudget(): bool
    {
        return $this->energyBudgets()->where('year', date('Y'))->exists();
    }

    /**
     * Check if this meter has a budget set for a specific year
     */
    public function hasBudgetForYear(int $year): bool
    {
        return $this->energyBudgets()->where('year', $year)->exists();
    }

    /**
     * Get the latest meter reading
     */
    public function latestReading()
    {
        return $this->hasOne(MeterReading::class)->latest('timestamp');
    }

    /**
     * Get meter readings for a specific date range
     */
    public function readingsForPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        return $this->readings()
            ->whereBetween('timestamp', [$start, $end])
            ->orderBy('timestamp');
    }

    /**
     * Get a human-readable description of what this meter measures
     */
    public function getMeasurementTypeString(): string
    {
        if ($this->measures_electricity && $this->measures_gas) {
            return 'Elektriciteit en Gas';
        } elseif ($this->measures_electricity) {
            return 'Elektriciteit';
        } elseif ($this->measures_gas) {
            return 'Gas';
        }
        
        return 'Onbekend';
    }

    /**
     * Check if this meter belongs to the current authenticated user
     */
    public function belongsToCurrentUser(): bool
    {
        return Auth::check() && $this->account_id === Auth::id();
    }

    /**
     * Scope to get meters for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('account_id', $userId);
    }

    /**
     * Scope to get active meters only
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get meters that measure electricity
     */
    public function scopeMeasuresElectricity($query)
    {
        return $query->where('measures_electricity', true);
    }

    /**
     * Scope to get meters that measure gas
     */
    public function scopeMeasuresGas($query)
    {
        return $query->where('measures_gas', true);
    }

    /**
     * Get all smart meters for the current authenticated user
     */
    public static function getAllSmartMetersForCurrentUser()
    {
        if (!Auth::check()) {
            return collect();
        }

        return static::forUser(Auth::id())
            ->active()
            ->orderBy('name')
            ->get();
    }

    /**
     * Get meter ID by database ID
     */
    public static function getMeterIdByDatabaseId(int $databaseId): ?string
    {
        $meter = static::find($databaseId);
        return $meter ? $meter->meter_id : null;
    }

    /**
     * Get database ID by meter ID
     */
    public static function getDatabaseIdByMeterId(string $meterId): ?int
    {
        $meter = static::where('meter_id', $meterId)->first();
        return $meter ? $meter->id : null;
    }

    /**
     * Get the budget target for a specific period and energy type
     */
    public function getBudgetTargetForPeriod(string $period, string $date, string $type): float
    {
        $budget = $this->getCurrentBudget();
        
        if (!$budget) {
            return 0;
        }
        
        return $budget->getBudgetForPeriod($period, $date, $type);
    }

    /**
     * Check if the meter needs budget setup
     */
    public function needsBudgetSetup(): bool
    {
        $budget = $this->getCurrentBudget();
        
        if (!$budget) {
            return true;
        }
        
        // Check if at least one energy type has a budget > 0
        $hasElectricityBudget = $this->measures_electricity && $budget->electricity_target_kwh > 0;
        $hasGasBudget = $this->measures_gas && $budget->gas_target_m3 > 0;
        
        return !($hasElectricityBudget || $hasGasBudget);
    }

    /**
     * Get summary information about this meter
     */
    public function getSummary(): array
    {
        $budget = $this->getCurrentBudget();
        
        return [
            'id' => $this->id,
            'meter_id' => $this->meter_id,
            'name' => $this->name,
            'location' => $this->location,
            'measures_electricity' => $this->measures_electricity,
            'measures_gas' => $this->measures_gas,
            'active' => $this->active,
            'has_budget' => $this->hasBudget(),
            'needs_budget_setup' => $this->needsBudgetSetup(),
            'budget' => $budget ? [
                'electricity_target_kwh' => $budget->electricity_target_kwh,
                'gas_target_m3' => $budget->gas_target_m3,
                'year' => $budget->year,
            ] : null,
            'measurement_types' => $this->getMeasurementTypeString(),
        ];
    }
}