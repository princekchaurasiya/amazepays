<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderOrder extends Model
{
    use HasFactory;

    protected $table = 'provider_orders';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'order_item_id',
        'provider',
        'provider_reference',
        'provider_order_id',
        'status',
        'attempt_count',
        'last_error_code',
        'last_error_message',
        'initiated_at',
        'completed_at',
    ];

    protected $casts = [
        'attempt_count' => 'integer',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }
}
