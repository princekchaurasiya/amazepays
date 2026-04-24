<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocumentVerification extends Model
{
    use HasFactory;

    protected $table = 'kyc_document_verifications';

    protected $fillable = [
        'kyc_document_id',
        'verified_by_user_id',
        'method',
        'outcome',
        'provider',
        'provider_reference',
        'raw_provider_response',
        'notes',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(KycDocument::class, 'kyc_document_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}

