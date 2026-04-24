<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferUsage extends Model
{
    use HasFactory;

    protected $table = 'offer_usages';

    protected $fillable = [
        'offer_id',
        'order_id',
        'user_id',
        'discount_amount_minor',
        'currency',
        'redeemed_at',
    ];

    protected $casts = [
        'discount_amount_minor' => 'integer',
        'redeemed_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
