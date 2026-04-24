<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderConnection extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'provider_connections';

    protected $fillable = [
        'tenant_id',
        'provider',
        'environment',
        'label',
        'public_config',
        'credentials_encrypted',
        'webhook_secret_encrypted',
        'callback_url',
        'webhook_url',
        'is_active',
        'connected_at',
        'last_credential_rotated_at',
        'last_successful_call_at',
    ];

    protected $casts = [
        'public_config' => 'array',
        'is_active' => 'boolean',
        'connected_at' => 'datetime',
        'last_credential_rotated_at' => 'datetime',
        'last_successful_call_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(ProviderSyncRun::class, 'connection_id');
    }
}

