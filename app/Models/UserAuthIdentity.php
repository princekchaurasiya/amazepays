<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAuthIdentity extends Model
{
    use HasFactory;

    protected $table = 'user_auth_identities';

    protected $fillable = [
        'user_id',
        'provider',
        'identifier',
        'display_identifier',
        'is_primary',
        'is_verified',
        'verified_at',
        'verification_token',
        'verification_sent_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'verification_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

