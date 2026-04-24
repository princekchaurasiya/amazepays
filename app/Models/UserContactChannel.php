<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserContactChannel extends Model
{
    use HasFactory;

    protected $table = 'user_contact_channels';

    protected $fillable = [
        'user_id',
        'channel',
        'value',
        'label',
        'is_primary',
        'is_verified',
        'opted_out',
        'preferences',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'opted_out' => 'boolean',
        'preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

