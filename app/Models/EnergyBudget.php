<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnergyBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'smart_meter_id',
        'year',
        'electricity_target_kwh',
        'electricity_target_euro',
        'gas_target_m3',
        'gas_target_euro',
    ];

    protected $casts = [
        'electricity_target_kwh' => 'decimal:2',
        'electricity_target_euro' => 'decimal:2',
        'gas_target_m3' => 'decimal:2',
        'gas_target_euro' => 'decimal:2',
        'year' => 'integer',
    ];

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with SmartMeter
     */
    public function smartMeter(): BelongsTo
    {
        return $this->belongsTo(SmartMeter::class);
    }

    /**
     * Relationship with MonthlyEnergyBudget
     */
    public function monthlyBudgets(): HasMany
    {
        return $this->hasMany(MonthlyEnergyBudget::class);
    }

    /**
     * Get the monthly budget for a specific month
     */
    public function getMonthlyBudget(int $month): ?MonthlyEnergyBudget
    {
        return $this->monthlyBudgets()->where('month', $month)->first();
    }

    /**
     * Get the electricity target for a specific month
     */
    public function getElectricityTargetForMonth(int $month): float
    {
        $monthlyBudget = $this->getMonthlyBudget($month);
        
        if ($monthlyBudget) {
            return $monthlyBudget->electricity_target_kwh;
        }
        
        // Fallback to yearly budget divided by 12
        return $this->electricity_target_kwh / 12;
    }

    /**
     * Get the gas target for a specific month
     */
    public function getGasTargetForMonth(int $month): float
    {
        $monthlyBudget = $this->getMonthlyBudget($month);
        
        if ($monthlyBudget) {
            return $monthlyBudget->gas_target_m3;
        }
        
        // Fallback to yearly budget divided by 12
        return $this->gas_target_m3 / 12;
    }

    /**
     * Get the total electricity target for all months
     */
    public function getTotalElectricityTarget(): float
    {
        $monthlyTotal = $this->monthlyBudgets()->sum('electricity_target_kwh');
        
        if ($monthlyTotal > 0) {
            return $monthlyTotal;
        }
        
        return $this->electricity_target_kwh;
    }

    /**
     * Get the total gas target for all months
     */
    public function getTotalGasTarget(): float
    {
        $monthlyTotal = $this->monthlyBudgets()->sum('gas_target_m3');
        
        if ($monthlyTotal > 0) {
            return $monthlyTotal;
        }
        
        return $this->gas_target_m3;
    }

    /**
     * Check if this budget is for electricity
     */
    public function hasElectricityBudget(): bool
    {
        return $this->electricity_target_kwh > 0;
    }

    /**
     * Check if this budget is for gas
     */
    public function hasGasBudget(): bool
    {
        return $this->gas_target_m3 > 0;
    }

    /**
     * Get the budget for a specific period (day, month, year)
     */
    public function getBudgetForPeriod(string $period, string $date, string $type): float
    {
        $dateObj = \Carbon\Carbon::parse($date);
        
        switch ($period) {
            case 'day':
                $monthlyTarget = $type === 'electricity' 
                    ? $this->getElectricityTargetForMonth($dateObj->month)
                    : $this->getGasTargetForMonth($dateObj->month);
                    
                return $monthlyTarget / $dateObj->daysInMonth;
                
            case 'month':
                return $type === 'electricity' 
                    ? $this->getElectricityTargetForMonth($dateObj->month)
                    : $this->getGasTargetForMonth($dateObj->month);
                    
            case 'year':
                return $type === 'electricity' 
                    ? $this->getTotalElectricityTarget()
                    : $this->getTotalGasTarget();
                    
            default:
                return 0;
        }
    }

    /**
     * Scope to get budgets for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get budgets for a specific meter
     */
    public function scopeForMeter($query, int $meterId)
    {
        return $query->where('smart_meter_id', $meterId);
    }

    /**
     * Scope to get budgets for a specific year
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to get current year budgets
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', date('Y'));
    }

    /**
     * Get all budgets for a user and year
     */
    public static function getBudgetsForUserAndYear(int $userId, int $year = null): \Illuminate\Database\Eloquent\Collection
    {
        $year = $year ?? date('Y');
        
        return static::forUser($userId)
            ->forYear($year)
            ->with(['smartMeter', 'monthlyBudgets' => function($query) {
                $query->orderBy('month');
            }])
            ->get();
    }

    /**
     * Get budget for a specific meter and year
     */
    public static function getBudgetForMeterAndYear(int $meterId, int $year = null): ?self
    {
        $year = $year ?? date('Y');
        
        return static::forMeter($meterId)
            ->forYear($year)
            ->with(['monthlyBudgets' => function($query) {
                $query->orderBy('month');
            }])
            ->first();
    }
}