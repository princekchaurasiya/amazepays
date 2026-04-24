<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDeviceContext extends Model
{
    use HasFactory;

    protected $table = 'order_device_context';

    protected $fillable = [
        'order_id',
        'device_fingerprint',
        'ip_address',
        'country_code',
        'user_agent',
        'is_vpn',
        'risk_score',
        'raw',
    ];

    protected $casts = [
        'is_vpn' => 'boolean',
        'risk_score' => 'integer',
        'raw' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

