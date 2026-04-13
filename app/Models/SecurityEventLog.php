<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEventLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_type', 'severity', 'ip_address', 'user_id', 'tenant_id',
        'user_agent', 'request_url', 'request_method', 'country_code', 'city',
        'is_vpn', 'device_id', 'metadata', 'resolved', 'resolved_by',
        'resolved_at', 'resolution_note', 'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_vpn' => 'boolean',
        'resolved' => 'boolean',
        'created_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Severity constants
    const SEVERITY_INFO = 'info';

    const SEVERITY_LOW = 'low';

    const SEVERITY_MEDIUM = 'medium';

    const SEVERITY_HIGH = 'high';

    const SEVERITY_CRITICAL = 'critical';

    // Event type constants
    const EVENT_VPN_DETECTED = 'vpn_detected';

    const EVENT_LOGIN_SUCCESS = 'login_success';

    const EVENT_LOGIN_FAILED = 'login_failed';

    const EVENT_ACCOUNT_LOCKED = 'account_locked';

    const EVENT_2FA_CHALLENGE = '2fa_challenge';

    const EVENT_2FA_FAILED = '2fa_failed';

    const EVENT_OTP_FAILED = 'otp_failed';

    const EVENT_TRANSACTION_PIN_FAILED = 'transaction_pin_failed';

    const EVENT_VOUCHER_CODE_ACCESSED = 'voucher_code_accessed';

    const EVENT_VOUCHER_ACCESS_VPN = 'voucher_access_vpn';

    const EVENT_VOUCHER_RATE_LIMIT = 'voucher_rate_limit_hit';

    const EVENT_WALLET_FRAUD_CHECK = 'wallet_fraud_check';

    const EVENT_WALLET_VELOCITY_EXCEEDED = 'wallet_velocity_exceeded';

    const EVENT_WALLET_DRAIN_ATTEMPT = 'wallet_drain_attempt';

    const EVENT_IP_AUTO_BLOCKED = 'ip_auto_blocked';

    const EVENT_IP_MANUAL_BLOCKED = 'ip_manual_blocked';

    const EVENT_ATTACK_PAYLOAD_DETECTED = 'attack_payload_detected';

    const EVENT_NEW_DEVICE_LOGIN = 'new_device_login';

    const EVENT_IMPOSSIBLE_TRAVEL = 'impossible_travel';

    const EVENT_MULTI_ACCOUNT_DETECTED = 'multi_account_detected';

    public static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (! $model->created_at) {
                $model->created_at = now();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeForIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }
}
