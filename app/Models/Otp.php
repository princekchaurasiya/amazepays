<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'mobile_number', 'otp', 'expiry_time'];

    protected $casts = [
        'expiry_time' => 'datetime',
    ];

    /**
     * Relationships
     */

    // User who owns this OTP
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */

    // Scope for valid (non-expired) OTPs
    public function scopeValid($query)
    {
        return $query->where('expiry_time', '>', now());
    }

    // Scope for expired OTPs
    public function scopeExpired($query)
    {
        return $query->where('expiry_time', '<=', now());
    }

    // Scope for specific mobile number
    public function scopeForMobile($query, $mobile)
    {
        return $query->where('mobile_number', $mobile);
    }

    /**
     * Helper Methods
     */

    // Check if OTP is valid
    public function isValid()
    {
        return $this->expiry_time > now();
    }

    // Check if OTP is expired
    public function isExpired()
    {
        return $this->expiry_time <= now();
    }
}