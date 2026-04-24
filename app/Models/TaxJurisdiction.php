<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxJurisdiction extends Model
{
    use HasFactory;

    protected $table = 'tax_jurisdictions';

    protected $fillable = [
        'code',
        'name',
        'scope',
        'country_code',
        'state_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

