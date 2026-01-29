<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'reference',
        'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */

    // Wallet
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    // User through wallet
    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            Wallet::class,
            'id',         // Foreign key on Wallet table
            'id',         // Foreign key on User table
            'wallet_id',  // Local key on WalletTransaction table
            'user_id'     // Local key on Wallet table
        );
    }

    /**
     * Scopes
     */

    // Scope for credit transactions
    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    // Scope for debit transactions
    public function scopeDebit($query)
    {
        return $query->where('type', 'debit');
    }

    // Scope for recent transactions
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
