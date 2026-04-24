<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOtpCode extends Model
{
    use HasFactory;

    protected $table = 'user_otp_codes';

    protected $fillable = [
        'user_id',
        'purpose',
        'identifier',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
        'ip_address',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

