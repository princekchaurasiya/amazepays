<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'order_number',
        'product_id',
        'status',
        'woohoo_order_id', 'order_status', 'denomination', 'sender_first_name',
        'sender_email', 'sender_phone_no', 'sender_post_code', 'sender_address_1',
        'sender_address_2', 'sender_city', 'sender_state', 'sku', 'amount',
        'receiver_name', 'receiver_email', 'receiver_mobile', 'receiver_msg',
        'gift_theme_id', 'gift_message_title', 'gift_delivery_option', 'gift_delivery_at',
        'cards', 'order_cancel', 'order_payment', 'payment_method', 'currency', 'additionalTxnFields',
        'grand_payable_amount', 'grand_total', 'discounted_amount_value', 'amount_payable_after_discount',
        'unit_price', 'subtotal', 'discount_percentage', 'discount_amount', 'gst_percentage', 'gst_amount',
        'offer_code', 'gift_option',
        'gst_number', 'country', 'merchant_order_id', 'refno', 'product_name',
        'quantity', 'gift_send_option', 'delivery_mode', 'vd_brand_code', 'vd_discount',
        'price', 'offer_id', 'offer_discount', 'device_fingerprint', 'purchase_ip',
        'purchase_country', 'code_view_count', 'last_code_viewed_at', 'is_vpn_purchase',
        'maker_id', 'checker_id', 'checker_action_at',
        'idempotency_key',
        'billing_name', 'billing_email', 'billing_tel', 'billing_address', 'billing_address_two',
        'billing_city', 'billing_state', 'billing_zip', 'billing_country', 'billing_gst_number',
        'voucher_code', 'voucher_pin', 'expiry_date',
        'vouchagram_reference_num', 'vouchagram_external_order_id', 'vouchagram_voucher_data',
    ];

    protected $casts = [
        'cards' => 'array',
        'vouchagram_voucher_data' => 'array',
        'additionalTxnFields' => 'array',
        'amount' => 'decimal:2',
        'grand_payable_amount' => 'decimal:2',
        'discounted_amount_value' => 'decimal:2',
        'amount_payable_after_discount' => 'decimal:2',
        'vd_discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'price' => 'decimal:2',
        'expiry_date' => 'date',
        'last_code_viewed_at' => 'datetime',
        'checker_action_at' => 'datetime',
        'is_vpn_purchase' => 'boolean',
        'gift_theme_id' => 'integer',
        'gift_delivery_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'sku', 'sku');
    }

    public function orderSummary(): HasOne
    {
        return $this->hasOne(OrderSummary::class, 'order_id');
    }

    public function ccAvenuePayment(): HasOne
    {
        return $this->hasOne(CcAvenuePayment::class, 'order_id');
    }

    public function unlimitPayment(): HasOne
    {
        return $this->hasOne(UnlimitPayment::class, 'order_id');
    }

    public function offerUsages(): HasMany
    {
        return $this->hasMany(OfferUsage::class, 'order_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'order_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('order_status', 'COMPLETE');
    }

    public function scopePending($query)
    {
        return $query->where('order_status', 'PENDING');
    }
}
