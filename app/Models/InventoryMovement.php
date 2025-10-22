<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'origin_warehouse_id',
        'target_warehouse_id',
        'order_id',
        'user_id',
        'moved_at',
        'notes',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
    ];

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(InventoryMovementDetail::class);
    }
}
