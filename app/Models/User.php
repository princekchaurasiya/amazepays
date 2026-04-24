<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'display_name',
        'account_type',
        'status',
        'is_super_admin',
        'last_login_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'is_super_admin' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function authIdentities(): HasMany
    {
        return $this->hasMany(UserAuthIdentity::class);
    }

    public function authSecrets(): HasMany
    {
        return $this->hasMany(UserAuthSecret::class);
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(UserOtpCode::class);
    }

    public function contactChannels(): HasMany
    {
        return $this->hasMany(UserContactChannel::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    public function twoFactorSecret(): HasOne
    {
        return $this->hasOne(TwoFactorSecret::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function ips(): HasMany
    {
        return $this->hasMany(UserIp::class);
    }

    public function transactionPin(): HasOne
    {
        return $this->hasOne(TransactionPin::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            // Create a default INR wallet for tenant-scoped users (skip platform super-admins).
            if (! $user->tenant_id) {
                return;
            }

            Wallet::firstOrCreate(
                ['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'currency' => 'INR'],
                [
                    'status' => 'active',
                    'balance' => 0,
                    'is_frozen' => false,
                    'available_balance_minor' => 0,
                    'held_balance_minor' => 0,
                ]
            );
        });
    }
}
