<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    protected $fillable = [
        'access_token',
        'expires_at',
    ];

    protected $dates = [
        'expires_at',
    ];
}
