<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftCard extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'gift_cards';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'order_item_id',
        'provider_order_id',
        'product_id',
        'provider',
        'card_number_encrypted',
        'card_pin_encrypted',
        'card_last4',
        'external_card_id',
        'face_value_minor',
        'currency',
        'status',
        'valid_from',
        'valid_until',
        'issued_at',
        'redeemed_at',
    ];

    protected $casts = [
        'face_value_minor' => 'integer',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'issued_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'card_number_encrypted' => 'encrypted',
        'card_pin_encrypted' => 'encrypted',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function providerOrder(): BelongsTo
    {
        return $this->belongsTo(ProviderOrder::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(GiftCardEvent::class);
    }
}
