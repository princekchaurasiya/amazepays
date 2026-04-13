<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLoadRequest extends Model
{
    protected $fillable = [
        'user_id', 'tenant_id', 'amount', 'payment_method', 'utr_number',
        'bank_reference', 'payment_proof_path', 'status', 'admin_note',
        'approved_by', 'approved_at', 'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['pending', 'under_review']);
    }
}
