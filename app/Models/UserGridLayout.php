<?php

namespace App\Models;

use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGridLayout extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'layout',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'layout' => 'array',
    ];

    /**
     * Get the user that owns the grid layout.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getSelectedSmartMeterForCurrentUser(){   

        return self::where('user_id', auth()->id())->value('selected_smartmeter');
    }
}