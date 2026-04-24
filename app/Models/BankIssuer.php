<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankIssuer extends Model
{
    use HasFactory;

    protected $table = 'bank_issuers';

    protected $fillable = [
        'name',
        'short_code',
        'logo_url',
        'country',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function paymentInstruments(): HasMany
    {
        return $this->hasMany(PaymentInstrument::class, 'bank_issuer_id');
    }

    public function bankOffers(): HasMany
    {
        return $this->hasMany(BankOffer::class, 'bank_issuer_id');
    }
}

