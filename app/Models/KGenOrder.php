<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KGenOrder extends Model
{
    use HasFactory;
    protected $fillable = [
    'variant_id',
    'external_ref',
    'mrp',
    'api_response',
];
}
