<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    use HasFactory;

    protected $table = 'payment_events';

    protected $fillable = [
        'payment_id',
        'source',
        'event_type',
        'gateway_status',
        'raw_payload',
        'signature_header',
        'signature_verified',
        'received_from_ip',
        'dispatch_id',
        'occurred_at',
    ];

    protected $casts = [
        'signature_verified' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}

