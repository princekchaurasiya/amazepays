<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'support_tickets';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'order_id',
        'assigned_to_user_id',
        'ticket_number',
        'subject',
        'status',
        'priority',
        'category',
        'subject_email',
        'subject_mobile',
        'first_response_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function generateTicketNumber(): string
    {
        return 'TKT-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('sent_at');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(SupportTicketStatusHistory::class, 'ticket_id')->orderBy('occurred_at');
    }
}
