<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSummary extends Model
{
    use HasFactory;

    protected $table = 'order_summary';

    protected $fillable = [
        'order_id',
        'payment_id',
        'payment_gateway', // 'cc_avenue' or 'unlimit'
        'sender_name',
        'sender_email',
        'sender_phone',
        'payment_status',
        'order_status',
        'product_name',
        'amount',
        // Add any other fields that need to be updated via mass assignment
    ];

    public function getSummaryStatusAttribute()
    {
        // Normalize payment_status: 'Paid' and 'Success' both mean payment succeeded
        $isPaymentSuccess = in_array($this->payment_status, ['Success', 'Paid', 'success', 'paid']);
        
        // Check if order is COMPLETE (Woohoo order created successfully)
        $isOrderComplete = strtoupper($this->order_status ?? '') === 'COMPLETE';
        
        if ($isPaymentSuccess && $isOrderComplete) {
            return 'complete';
        } elseif ($isPaymentSuccess) {
            // Payment successful but Woohoo order not yet created (PENDING) or failed
            return 'resend';
        }
        return 'incomplete';
    }

    /**
     * Get the payment record based on payment_gateway
     */
    public function getPaymentAttribute()
    {
        if (!$this->payment_id) {
            return null;
        }

        if ($this->payment_gateway === 'cc_avenue') {
            return \App\Models\CcAvenuePayment::find($this->payment_id);
        } elseif ($this->payment_gateway === 'unlimit') {
            return \App\Models\UnlimitPayment::find($this->payment_id);
        }

        return null;
    }

    /**
     * Relationships
     */

    // Order relationship
    public function order()
    {
        return $this->belongsTo(QsOrder::class, 'order_id', 'id');
    }

    // User through order
    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            QsOrder::class,
            'id',        // Foreign key on QsOrder table
            'id',        // Foreign key on User table
            'order_id',  // Local key on OrderSummary table
            'user_id'    // Local key on QsOrder table
        );
    }

    // CC Avenue Payment
    public function ccAvenuePayment()
    {
        return $this->belongsTo(CcAvenuePayment::class, 'payment_id')->where('payment_gateway', 'cc_avenue');
    }

    // Unlimit Payment
    public function unlimitPayment()
    {
        return $this->belongsTo(UnlimitPayment::class, 'payment_id')->where('payment_gateway', 'unlimit');
    }

    /**
     * Scopes
     */

    // Scope for completed orders
    public function scopeCompleted($query)
    {
        return $query->where('order_status', 'COMPLETE')
                    ->whereIn('payment_status', ['Success', 'Paid', 'success', 'paid']);
    }

    // Scope for pending orders
    public function scopePending($query)
    {
        return $query->where('order_status', 'PENDING')
                    ->whereIn('payment_status', ['Success', 'Paid', 'success', 'paid']);
    }

    // Scope for failed orders
    public function scopeFailed($query)
    {
        return $query->whereNotIn('payment_status', ['Success', 'Paid', 'success', 'paid']);
    }
}
