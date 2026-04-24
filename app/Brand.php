<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_code',
        'brand_name',
        'brand_type',
        'discount',
        'min_price',
        'max_price',
        'denomination_list',
        'stock_available',
        'category',
        'description',
        'images',
        'tnc',
        'important_instruction',
        'redeem_steps',
    ];

    protected $casts = [
        'images' => 'array',
        'important_instruction' => 'array',
        'redeem_steps' => 'array',
    ];
}
