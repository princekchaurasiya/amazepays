<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'currency',
        'balance',
        'is_frozen',
        'available_balance_minor',
        'held_balance_minor',
        'frozen_at',
        'frozen_reason',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_frozen' => 'boolean',
        'available_balance_minor' => 'integer',
        'held_balance_minor' => 'integer',
        'frozen_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
