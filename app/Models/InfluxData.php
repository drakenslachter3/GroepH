<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfluxData extends Model
{
    use HasFactory;

    protected $fillable = [
        'measurement',
        'tags',
        'fields',
        'time',
    ];

    protected $casts = [
        'tags' => 'array',
        'fields' => 'array',
        'time' => 'datetime',
    ];
}