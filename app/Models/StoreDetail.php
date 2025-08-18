<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreDetail extends Model
{
    use HasFactory;
    protected $table = 'store_details'; // explicitly set if needed

    protected $fillable = [
        'store_code',    // optional if you have it
        'brand_code',
        'brand_name',
        'address',
        'city',
        'state',
        'country',
        'contact_number',
    ];
}
