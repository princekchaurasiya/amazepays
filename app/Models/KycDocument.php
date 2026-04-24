<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KycDocument extends Model
{
    use HasFactory;

    protected $table = 'kyc_documents';

    protected $fillable = [
        'kyc_profile_id',
        'user_id',
        'document_type',
        'number_encrypted',
        'number_hash',
        'name_on_document',
        'front_image_url',
        'back_image_url',
        'status',
        'submitted_at',
        'verified_at',
        'rejection_reason',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(KycProfile::class, 'kyc_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(KycDocumentVerification::class);
    }
}

