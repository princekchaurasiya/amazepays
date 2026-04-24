<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvcCardItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_code',
        'product_name',
        'card_no',
        'card_pin',
        'card_status',
        'expiry_date',
        'balance_basic',
        'balance_bonus',
        'balance_total',
        'bonus_given',
        'deal_no',
        'receipt_no',
    ];
}
