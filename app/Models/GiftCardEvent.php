<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCardEvent extends Model
{
    protected $table = 'gift_card_events';

    protected $fillable = [
        'gift_card_id',
        'event_type',
        'delta_amount_minor',
        'balance_after_minor',
        'external_event_id',
        'raw_payload',
        'occurred_at',
    ];

    protected $casts = [
        'delta_amount_minor' => 'integer',
        'balance_after_minor' => 'integer',
        'occurred_at' => 'datetime',
    ];

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }
}
