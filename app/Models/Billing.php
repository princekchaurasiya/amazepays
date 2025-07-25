<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;
    protected $fillable = [
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
}
