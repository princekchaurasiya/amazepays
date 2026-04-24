<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'request_id',
        'request_time',
        'amount',
        'amount_decimal',
        'currency',
        'expire_at',
        'merchant_order_id',
        'items',
        'customer_email',
        'payment_method',
        'api_response',
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'request_time' => 'datetime',
        'amount_decimal' => 'decimal:2',
    ];
}
