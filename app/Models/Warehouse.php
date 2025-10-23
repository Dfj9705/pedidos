<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_route',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'is_route' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
