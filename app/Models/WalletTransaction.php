<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $table = 'wallet_transactions';

    protected $fillable = [
        'tenant_id',
        'wallet_id',
        'direction',
        'reason',
        'amount_minor',
        'currency',
        'running_balance_minor',
        'idempotency_key',
        'reference',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'running_balance_minor' => 'integer',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
