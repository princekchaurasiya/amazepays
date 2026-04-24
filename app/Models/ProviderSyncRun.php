<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderSyncRun extends Model
{
    use HasFactory;

    protected $table = 'provider_sync_runs';

    protected $fillable = [
        'connection_id',
        'job_type',
        'status',
        'records_fetched',
        'records_created',
        'records_updated',
        'records_skipped',
        'records_failed',
        'last_error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'records_fetched' => 'integer',
        'records_created' => 'integer',
        'records_updated' => 'integer',
        'records_skipped' => 'integer',
        'records_failed' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class, 'connection_id');
    }

    public function apiCallLogs(): HasMany
    {
        return $this->hasMany(ProviderApiCallLog::class, 'sync_run_id');
    }
}

