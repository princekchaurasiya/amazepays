<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'order_number',
        'channel',
        'status',
        'subtotal_minor',
        'discount_total_minor',
        'tax_total_minor',
        'grand_total_minor',
        'currency',
        'placed_at',
        'refno',
        'merchant_order_id',
        'woohoo_order_id',
        'order_status',
        'invoice_number',
        'product_name',
        'sku',
        'quantity',
        'denomination',
        'price',
        'amount_payable_after_discount',
        'grand_payable_amount',
        'cards',
        'order_cancel',
        'order_payment',
        'additional_txn_fields',
        'woohoo_currency_snapshot',
        'gift_send_option',
        'receiver_name',
        'receiver_email',
        'receiver_mobile',
        'receiver_msg',
        'delivery_mode',
        'gift_theme_id',
        'gift_message_title',
        'sender_first_name',
        'gift_delivery_option',
        'gift_delivery_at',
    ];

    protected $casts = [
        'subtotal_minor' => 'integer',
        'discount_total_minor' => 'integer',
        'tax_total_minor' => 'integer',
        'grand_total_minor' => 'integer',
        'placed_at' => 'datetime',
        'quantity' => 'integer',
        'denomination' => 'decimal:4',
        'price' => 'decimal:4',
        'amount_payable_after_discount' => 'decimal:4',
        'grand_payable_amount' => 'decimal:4',
        'order_cancel' => 'array',
        'order_payment' => 'array',
        'additional_txn_fields' => 'array',
        'woohoo_currency_snapshot' => 'array',
        'gift_delivery_at' => 'datetime',
        'gift_theme_id' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function billingSnapshot(): HasOne
    {
        return $this->hasOne(OrderBillingSnapshot::class);
    }

    public function shippingSnapshot(): HasOne
    {
        return $this->hasOne(OrderShippingSnapshot::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class);
    }

    public function taxBreakdowns(): HasManyThrough
    {
        return $this->hasManyThrough(
            OrderTaxBreakdown::class,
            OrderItem::class,
            'order_id',
            'order_item_id',
            'id',
            'id',
        );
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function deviceContext(): HasOne
    {
        return $this->hasOne(OrderDeviceContext::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(OrderApproval::class);
    }

    public function codeReveals(): HasMany
    {
        return $this->hasMany(OrderCodeReveal::class);
    }

    public function summary(): HasOne
    {
        return $this->hasOne(OrderSummary::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function providerOrders(): HasMany
    {
        return $this->hasMany(ProviderOrder::class);
    }

    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }

    public function offerUsages(): HasMany
    {
        return $this->hasMany(OfferUsage::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function kycRequirement(): HasOne
    {
        return $this->hasOne(OrderKycRequirement::class);
    }
}
