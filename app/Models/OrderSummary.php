<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSummary extends Model
{
    use HasFactory;

    protected $table = 'order_summary';

    protected $fillable = [
        'order_id',
        'payment_id',
        'payment_gateway',
        'sender_name',
        'sender_email',
        'sender_phone',
        'payment_status',
        'order_status',
        'summary_status',
        'product_name',
        'amount',
    ];

    protected function summaryStatus(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (filled($value)) {
                    return $value;
                }

                $isPaymentSuccess = in_array($this->payment_status, ['Success', 'Paid', 'success', 'paid']);
                $isOrderComplete = strtoupper($this->order_status ?? '') === 'COMPLETE';

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

    public function getPaymentAttribute()
    {
        if (! $this->payment_id) {
            return null;
        }

        if ($this->payment_gateway === 'cc_avenue') {
            return CcAvenuePayment::find($this->payment_id);
        }
        if ($this->payment_gateway === 'unlimit') {
            return UnlimitPayment::find($this->payment_id);
        }

        return null;
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function user()
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

    public function ccAvenuePayment()
    {
        return $this->belongsTo(CcAvenuePayment::class, 'payment_id')->where('payment_gateway', 'cc_avenue');
    }

    public function unlimitPayment()
    {
        return $this->belongsTo(UnlimitPayment::class, 'payment_id')->where('payment_gateway', 'unlimit');
    }

    public function scopeCompleted($query)
    {
        return $query->where('order_status', 'COMPLETE')
            ->whereIn('payment_status', ['Success', 'Paid', 'success', 'paid']);
    }

    public function scopePending($query)
    {
        return $query->where('order_status', 'PENDING')
            ->whereIn('payment_status', ['Success', 'Paid', 'success', 'paid']);
    }

    public function scopeFailed($query)
    {
        return $query->whereNotIn('payment_status', ['Success', 'Paid', 'success', 'paid']);
    }
}
