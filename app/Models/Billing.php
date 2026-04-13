<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Billing extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'billing_name',
        'billing_email',
        'billing_tel',
        'billing_zip',
        'billing_address',
        'billing_address_two',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_gst_number',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
