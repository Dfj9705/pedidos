<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'category_id',
        'sku',
        'name',
        'description',
        'cost',
        'price',
        'min_stock',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:4',
        'price' => 'decimal:4',
        'min_stock' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
