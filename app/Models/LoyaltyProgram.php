<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $table = 'loyalty_programs';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'point_currency_label',
        'point_to_currency_rate',
        'currency',
        'status',
        'points_expiry_days',
    ];

    protected $casts = [
        'point_to_currency_rate' => 'decimal:6',
        'points_expiry_days' => 'integer',
    ];
}

