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
        'sender_name',
        'sender_email',
        'sender_phone',
        'payment_status',
        'amount',
        // Add any other fields that need to be updated via mass assignment
    ];

    public function getSummaryStatusAttribute()
    {
        if ($this->payment_status === 'Success' && $this->order_status === 'COMPLETE') {
            return 'complete';
        } elseif ($this->payment_status === 'Success') {
            return 'resend';
        }
        return 'incomplete';
    }

    // Specify the primary key if it is not 'id'
    // protected $primaryKey = 'your_primary_key_column_name';

    // Disable timestamps if necessary
    // public $timestamps = false;
}
