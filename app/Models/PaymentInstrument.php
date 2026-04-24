<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentInstrument extends Model
{
    use HasFactory;

    protected $table = 'payment_instruments';

    protected $fillable = [
        'bank_issuer_id',
        'name',
        'slug',
        'category',
        'card_type',
        'card_network',
        'supports_emi',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'supports_emi' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(BankIssuer::class, 'bank_issuer_id');
    }
}

