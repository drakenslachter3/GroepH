<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PredictionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'period',
        'best_case_margin',
        'worst_case_margin',
        'active'
    ];

    // Get settings for a specific type and period
    public static function getSettings(string $type = 'global', string $period = 'all')
    {
        // Try to get specific settings first
        $settings = self::where('type', $type)
                        ->where('period', $period)
                        ->where('active', true)
                        ->first();
        
        // If no specific settings, try type with 'all' period
        if (!$settings) {
            $settings = self::where('type', $type)
                            ->where('period', 'all')
                            ->where('active', true)
                            ->first();
        }
        
        // If still no settings, use global defaults
        if (!$settings) {
            $settings = self::where('type', 'global')
                            ->where('period', 'all')
                            ->where('active', true)
                            ->first();
        }
        
        // If no settings in DB, return default values
        if (!$settings) {
            return [
                'best_case_margin' => 10.00,
                'worst_case_margin' => 15.00
            ];
        }
        
        return $settings;
    }
}