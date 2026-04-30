<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'denomination_id',
        'sku_snapshot',
        'name_snapshot',
        'quantity',
        'unit_amount_minor',
        'line_subtotal_minor',
        'line_discount_minor',
        'line_tax_minor',
        'line_total_minor',
        'currency',
        'fulfilment_status',
        'gift_theme_id',
        'gift_send_option',
        'gift_message_title',
        'gift_delivery_option',
        'gift_delivery_at',
        'sender_first_name',
        'receiver_name',
        'receiver_email',
        'receiver_mobile',
        'receiver_msg',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_amount_minor' => 'integer',
        'line_subtotal_minor' => 'integer',
        'line_discount_minor' => 'integer',
        'line_tax_minor' => 'integer',
        'line_total_minor' => 'integer',
        'gift_delivery_at' => 'datetime',
        'gift_theme_id' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function providerOrders(): HasMany
    {
        return $this->hasMany(ProviderOrder::class);
    }
}
