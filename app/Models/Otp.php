<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mobile_number',
        'otp',
        'type',
        'is_used',
        'attempts',
        'ip_address',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_used' => 'boolean',
        'attempts' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    public function scopeForMobile($query, string $mobile)
    {
        return $query->where('mobile_number', $mobile);
    }

    public function isValid(): bool
    {
        return $this->expires_at > now();
    }

    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }
}
