<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankOfferUsage extends Model
{
    use HasFactory;

    protected $table = 'bank_offer_usages';

    protected $fillable = [
        'bank_offer_id',
        'payment_id',
        'order_id',
        'user_id',
        'discount_amount_minor',
        'currency',
        'applied_at',
    ];

    protected $casts = [
        'discount_amount_minor' => 'integer',
        'applied_at' => 'datetime',
    ];

    public function bankOffer(): BelongsTo
    {
        return $this->belongsTo(BankOffer::class, 'bank_offer_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

