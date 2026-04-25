<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class UrlRedirect extends Model
{
    use SoftDeletes;

    protected $table = 'url_redirects';

    protected $fillable = [
        'tenant_id',
        'created_by_user_id',
        'source_path',
        'destination_url',
        'http_status_code',
        'match_mode',
        'is_active',
        'hit_count',
        'last_hit_at',
        'active_from',
        'active_until',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'hit_count' => 'integer',
        'last_hit_at' => 'datetime',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'http_status_code' => 'integer',
    ];
}

