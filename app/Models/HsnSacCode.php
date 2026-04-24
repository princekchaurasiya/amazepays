<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HsnSacCode extends Model
{
    use HasFactory;

    protected $table = 'hsn_sac_codes';

    protected $fillable = [
        'code',
        'type',
        'description',
        'chapter',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

