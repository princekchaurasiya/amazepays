<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KGenOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'variant_id',
        'external_ref',
        'mrp',
        'payable_amount',
        'selling_price',
        'status',
        'payment_status',
        'fulfillment_status',
        'api_response',
        'vouchers'
        // ... other fields
    ];

    protected $casts = [
        'api_response' => 'array',
        'vouchers' => 'array',
        'mrp' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    /**
     * Relationship to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for user orders
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
