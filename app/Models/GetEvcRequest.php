<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GetEvcRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'distributor_id',
        'sku_code',
        'no_of_card',
        'amount',
        'receipt_no',
        'req_id',
        'firstname',
        'lastname',
        'email',
        'mobile_no',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'curr',
    ];
}
