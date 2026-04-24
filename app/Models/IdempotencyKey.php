<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class IdempotencyKey extends Model
{
    protected $table = 'idempotency_keys';

    protected $fillable = [
        'scope',
        'idempotency_key',
        'user_id',
        'request_hash',
        'response_status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];
}
