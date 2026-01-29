<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'payment_method',
        'merchant_order_id',
        'merchant_order_description',
        'payment_id',
        'type',
        'status',
        'amount',
        'currency',
        'created_at_api',
        'decline_reason',
        'decline_code',
        'is_3d',
        'arn',
        'rrn',
        'original_amount',
        'masked_pan',
        'holder',
        'issuing_country_code',
        'customer_email',
        'customer_ip',
        'customer_locale',
    ];

    protected $casts = [
        'is_3d' => 'boolean',
        'created_at_api' => 'datetime',
        'amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */

    // User who made the payment (if user_id exists in table)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Order associated with payment (if order_id exists)
    public function order()
    {
        return $this->belongsTo(QsOrder::class, 'merchant_order_id', 'merchant_order_id');
    }
}
