<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDiscount extends Model
{
    use HasFactory;

    protected $table = 'order_discounts';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'source_type',
        'source_ref_id',
        'code',
        'label',
        'amount_minor',
        'currency',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}

