<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnergyBudgetMargin extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'type',
        'margin_percentage',
    ];

    /**
     * Get the user that owns the margin setting.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}