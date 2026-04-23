<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'gift_theme_id',
        'product_slug',
        'sku',
        'product_name',
        'gift_send_option',
        'denomination',
        'quantity',
        'line_total',
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
        'gift_theme_id' => 'integer',
        'denomination' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity' => 'integer',
        'gift_delivery_at' => 'datetime',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function giftTheme(): BelongsTo
    {
        return $this->belongsTo(GiftCardTheme::class, 'gift_theme_id');
    }
}
