<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeliveryRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'warehouse_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)
            ->withPivot(['position', 'delivered_at', 'delivered_by'])
            ->orderByPivot('position');
    }
}
