<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class OrderSummary extends Model
{
    use HasFactory;

    protected $table = 'order_summaries';

    protected $fillable = [
        'order_id',
        'payment_id',
        'item_count',
        'distinct_product_count',
        'primary_brand_name',
        'primary_gateway',
        'payment_status',
        'fulfilment_status',
        'meta',
        'product_name',
        'sender_name',
        'sender_email',
        'sender_phone',
        'amount_minor',
    ];

    protected $casts = [
        'meta' => 'array',
        'item_count' => 'integer',
        'distinct_product_count' => 'integer',
        'amount_minor' => 'integer',
    ];

    protected function summaryStatus(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (filled($value)) {
                    return $value;
                }

                $isPaymentSuccess = in_array($this->payment_status, ['Success', 'Paid', 'success', 'paid', 'captured'], true);
                $isOrderComplete = strtoupper($this->fulfilment_status ?? '') === 'COMPLETE';

                if ($isPaymentSuccess && $isOrderComplete) {
                    return 'complete';
                }
                if ($isPaymentSuccess) {
                    return 'resend';
                }

                return 'incomplete';
            },
        );
    }

    public function getPaymentAttribute(): ?Payment
    {
        if (! $this->payment_id) {
            return null;
        }

        if (in_array($this->primary_gateway, ['cc_avenue', 'ccavenue', 'unlimit', 'razorpay'], true)) {
            return Payment::find($this->payment_id);
        }

        return null;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Order::class,
            'id',
            'id',
            'order_id',
            'user_id'
        );
    }

    public function ccAvenuePayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id')->where('gateway', 'ccavenue');
    }

    public function unlimitPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id')->where('gateway', 'unlimit');
    }

    public function scopeCompleted($query)
    {
        return $query->where('fulfilment_status', 'COMPLETE')
            ->whereIn('payment_status', ['Success', 'Paid', 'success', 'paid', 'captured']);
    }

    public function scopePending($query)
    {
        return $query->where('fulfilment_status', 'PENDING')
            ->whereIn('payment_status', ['Success', 'Paid', 'success', 'paid', 'captured']);
    }

    public function scopeFailed($query)
    {
        return $query->whereNotIn('payment_status', ['Success', 'Paid', 'success', 'paid', 'captured']);
    }
}
