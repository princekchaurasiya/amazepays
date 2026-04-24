<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShippingSnapshot extends Model
{
    use HasFactory;

    protected $table = 'order_shipping_snapshots';

    protected $fillable = [
        'order_id',
        'user_address_id',
        'full_name',
        'phone',
        'line1',
        'line2',
        'city',
        'state',
        'postal_code',
        'country',
        'courier',
        'tracking_number',
        'shipped_at',
        'delivered_at',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

