<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QsOrder extends Model
{
    use HasFactory;

    protected $table = 'qs_orders';

    protected $fillable = [
        'user_id', // SECURITY: Required for order ownership verification
        'woohoo_order_id', 'order_status', 'denomination', 'sender_first_name',
        'sender_email', 'sender_phone_no', 'sender_post_code', 'sender_address_1',
        'sender_address_2', 'sender_city', 'sender_state', 'sku', 'amount',
        'receiver_name', 'receiver_email', 'receiver_mobile', 'receiver_msg',
        'cards', 'order_cancel', 'order_payment', 'currency', 'additionalTxnFields',
        'grand_payable_amount', 'discounted_amount_value', 'amount_payable_after_discount',
        'gst_number', 'country', 'merchant_order_id', 'refno', 'product_name',
        'quantity', 'gift_send_option', 'delivery_mode', 'vd_brand_code', 'vd_discount',
        'price'
    ];

    protected $casts = [
        'cards' => 'array',
        'additionalTxnFields' => 'array',
        'amount' => 'decimal:2',
        'grand_payable_amount' => 'decimal:2',
        'discounted_amount_value' => 'decimal:2',
        'amount_payable_after_discount' => 'decimal:2',
        'vd_discount' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    /**
     * Relationships
     */

    // User who placed the order
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Product ordered
    public function product()
    {
        return $this->belongsTo(QsProduct::class, 'sku', 'sku');
    }

    // Order summary
    public function orderSummary()
    {
        return $this->hasOne(OrderSummary::class, 'order_id');
    }

    // CC Avenue payment for this order
    public function ccAvenuePayment()
    {
        return $this->hasOne(CcAvenuePayment::class, 'order_id');
    }

    // Unlimit payment for this order
    public function unlimitPayment()
    {
        return $this->hasOne(UnlimitPayment::class, 'order_id');
    }

    /**
     * Scopes
     */

    // Scope for user orders
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope for completed orders
    public function scopeCompleted($query)
    {
        return $query->where('order_status', 'COMPLETE');
    }

    // Scope for pending orders
    public function scopePending($query)
    {
        return $query->where('order_status', 'PENDING');
    }
}
