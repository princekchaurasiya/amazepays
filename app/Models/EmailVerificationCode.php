<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailVerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'code',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    // User who owns this verification code (if user_id exists)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */

    // Scope for valid (non-expired) codes
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    // Scope for expired codes
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    // Scope for specific email
    public function scopeForEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Helper Methods
     */

    // Check if verification code is valid
    public function isValid()
    {
        return $this->expires_at > now();
    }

    // Check if verification code is expired
    public function isExpired()
    {
        return $this->expires_at <= now();
    }
}