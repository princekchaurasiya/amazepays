<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'user_id',
        'gateway',
        'environment',
        'merchant_order_id',
        'gateway_payment_id',
        'gateway_reference',
        'status',
        'method_category',
        'method_detail',
        'amount_minor',
        'currency',
        'fee_minor',
        'tax_on_fee_minor',
        'settlement_amount_minor',
        'payment_instrument_id',
        'applied_bank_offer_id',
        'emi_tenure_months',
        'emi_processing_fee_minor',
        'idempotency_key',
        'failure_code',
        'failure_reason',
        'initiated_at',
        'authorized_at',
        'captured_at',
        'failed_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'fee_minor' => 'integer',
        'tax_on_fee_minor' => 'integer',
        'settlement_amount_minor' => 'integer',
        'emi_tenure_months' => 'integer',
        'emi_processing_fee_minor' => 'integer',
        'initiated_at' => 'datetime',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this payment is settled enough to trigger voucher fulfilment (Woohoo / etc.).
     */
    public function isSuccessfulForFulfillment(): bool
    {
        return in_array($this->status, ['captured', 'authorized'], true);
    }

    /**
     * Prefer exact merchant_order_id match, then any latest Unlimit row for the order.
     */
    public static function findUnlimitForOrder(Order $order, string $merchantOrderId): ?self
    {
        $exact = static::query()
            ->where('gateway', 'unlimit')
            ->where('order_id', $order->id)
            ->where('merchant_order_id', $merchantOrderId)
            ->first();

        return $exact ?? static::query()
            ->where('gateway', 'unlimit')
            ->where('order_id', $order->id)
            ->orderByDesc('id')
            ->first();
    }
}
