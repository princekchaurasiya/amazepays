<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazepayAvailableBrand extends Model
{
    use HasFactory;

    // Specify the table name if it's not the plural form of the model name
    protected $table = 'amazepay_available_brands';

    // The attributes that are mass assignable
    protected $fillable = [
        'name',      // The name of the brand
        'slug',      // Unique slug for routing
        'logo',      // Logo image for the brand
        'order'      // Order for sorting
    ];

}
