<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductTaxAssignment extends Model
{
    use HasFactory;

    protected $table = 'product_tax_assignments';

    protected $fillable = [
        'product_id',
        'hsn_sac_code_id',
        'jurisdiction_id',
        'override_rate_percent',
        'is_exempt',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'override_rate_percent' => 'decimal:3',
        'is_exempt' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];
}

