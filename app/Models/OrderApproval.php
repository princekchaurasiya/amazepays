<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderApproval extends Model
{
    use HasFactory;

    protected $table = 'order_approvals';

    protected $fillable = [
        'order_id',
        'type',
        'status',
        'queued_reason',
        'maker_user_id',
        'checker_user_id',
        'checker_action_at',
        'checker_notes',
    ];

    protected $casts = [
        'checker_action_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function maker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maker_user_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_user_id');
    }
}

