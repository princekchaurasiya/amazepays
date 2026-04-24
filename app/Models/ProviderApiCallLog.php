<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderApiCallLog extends Model
{
    use HasFactory;

    protected $table = 'provider_api_call_logs';

    protected $fillable = [
        'connection_id',
        'sync_run_id',
        'initiator_type',
        'initiator_id',
        'operation',
        'endpoint',
        'http_method',
        'response_status',
        'duration_ms',
        'request_hash',
        'request_payload_redacted',
        'response_payload_redacted',
        'signature_valid',
        'error_code',
        'error_message',
        'upstream_reference',
        'called_at',
    ];

    protected $casts = [
        'response_status' => 'integer',
        'duration_ms' => 'integer',
        'signature_valid' => 'boolean',
        'called_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class, 'connection_id');
    }

    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(ProviderSyncRun::class, 'sync_run_id');
    }
}

