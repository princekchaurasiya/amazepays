<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LoyaltyPointTransaction extends Model
{
    use HasFactory;

    protected $table = 'loyalty_point_transactions';

    protected $fillable = [
        'account_id',
        'direction',
        'reason',
        'points',
        'running_balance',
        'source_type',
        'source_id',
        'reference',
        'description',
        'occurred_at',
        'expires_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'running_balance' => 'integer',
        'occurred_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}

