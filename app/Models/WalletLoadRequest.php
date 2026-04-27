<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLoadRequest extends Model
{
    use HasFactory;

    protected $table = 'wallet_load_requests';

    protected $fillable = [
        'wallet_id',
        'payment_id',
        'amount_minor',
        'currency',
        'status',
        'failure_reason',
        'completed_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'initiated');
    }

    public function isPending(): bool
    {
        return $this->status === 'initiated';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'initiated';
    }
}
