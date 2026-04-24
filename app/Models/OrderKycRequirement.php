<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderKycRequirement extends Model
{
    use HasFactory;

    protected $table = 'order_kyc_requirements';

    protected $fillable = [
        'order_id',
        'kyc_threshold_id',
        'kyc_profile_id',
        'requirement_status',
        'enforcement',
        'required_document_types',
        'evaluated_amount_minor',
        'currency',
        'reason',
        'evaluated_at',
        'satisfied_at',
    ];

    protected $casts = [
        'required_document_types' => 'array',
        'evaluated_amount_minor' => 'integer',
        'evaluated_at' => 'datetime',
        'satisfied_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function threshold(): BelongsTo
    {
        return $this->belongsTo(KycThreshold::class, 'kyc_threshold_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(KycProfile::class, 'kyc_profile_id');
    }
}

