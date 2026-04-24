<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycThreshold extends Model
{
    use HasFactory;

    protected $table = 'kyc_thresholds';

    protected $fillable = [
        'tenant_id',
        'name',
        'scope',
        'channel',
        'threshold_amount_minor',
        'currency',
        'required_document_types',
        'enforcement',
        'is_active',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'threshold_amount_minor' => 'integer',
        'required_document_types' => 'array',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

