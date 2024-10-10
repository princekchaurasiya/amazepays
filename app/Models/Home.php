<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Home extends Model
{
    use HasFactory;

    protected $table = 'home'; // Specify the table name if different from the model name

    protected $fillable = [
        'section_banner_status', // To manage the show/hide status for the banner section
        // Add other fields that are applicable
    ];

    // If you have any relationships, you can define them here
}
