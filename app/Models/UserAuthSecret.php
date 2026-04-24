<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAuthSecret extends Model
{
    use HasFactory;

    protected $table = 'user_auth_secrets';

    protected $fillable = [
        'user_id',
        'identity_id',
        'password_hash',
        'failed_attempts',
        'locked_until',
        'last_rotated_at',
        'must_reset_at',
    ];

    protected $casts = [
        'failed_attempts' => 'integer',
        'locked_until' => 'datetime',
        'last_rotated_at' => 'datetime',
        'must_reset_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(UserAuthIdentity::class, 'identity_id');
    }
}

