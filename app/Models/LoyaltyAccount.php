<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LoyaltyAccount extends Model
{
    use HasFactory;

    protected $table = 'loyalty_accounts';

    protected $fillable = [
        'program_id',
        'user_id',
        'current_tier_id',
        'points_balance',
        'points_lifetime_earned',
        'points_lifetime_redeemed',
        'points_pending',
        'tier_expires_at',
    ];

    protected $casts = [
        'points_balance' => 'integer',
        'points_lifetime_earned' => 'integer',
        'points_lifetime_redeemed' => 'integer',
        'points_pending' => 'integer',
        'tier_expires_at' => 'datetime',
    ];
}

