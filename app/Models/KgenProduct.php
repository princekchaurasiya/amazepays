<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KgenProduct extends Model
{
    use HasFactory;
    protected $table = 'kgen_products';

    protected $fillable = [
        'productID',
        'productName',
        'productDisplayName',
        'descriptionText',
        'redemptionInstructions',
        'termsAndConditions',
        'attachments',
        'categories',
        'variants',
    ];

    protected $casts = [
        'attachments' => 'array',
        'categories'  => 'array',
        'variants'    => 'array',
    ];
}
