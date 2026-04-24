<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LoyaltyRedemption extends Model
{
    use HasFactory;

    protected $table = 'loyalty_redemptions';

    protected $fillable = [
        'account_id',
        'order_id',
        'point_transaction_id',
        'points_redeemed',
        'discount_amount_minor',
        'currency',
        'status',
        'applied_at',
    ];

    protected $casts = [
        'points_redeemed' => 'integer',
        'discount_amount_minor' => 'integer',
        'applied_at' => 'datetime',
    ];
}

