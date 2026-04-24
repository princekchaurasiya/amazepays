<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KycProfile extends Model
{
    use HasFactory;

    protected $table = 'kyc_profiles';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'status',
        'tier',
        'legal_name',
        'date_of_birth',
        'last_verified_at',
        'expires_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }
}

