<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KgenStoreOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'orderID',
        'externalRefID',
        'dpUserEmail',
        'dpUserName',
        'dpID',
        'totalOrderMRP',
        'totalAmount',
        'status',
        'fulfillmentStatus',
        'createdAt',
        'updatedAt',
        'assetURL',
        'type',
        'variantID',
        'productID',
        'quantity',
        'mrp',
        'price',
        'totalMRP',
        'totalPrice',
        'variantName',
        'productName',
        'variantDisplayName',
        'productDisplayName',
        'attachments',
        'mobileNumbers',
        'vouchers',
        'fulfillmentStatus',
        'allocatedQty',
        'fulfilledQty'
    ];

}
