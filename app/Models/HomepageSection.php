<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    use HasFactory;
    protected $table = 'homepage_sections'; // optional if table name matches
    protected $fillable = ['section_name', 'content', 'status'];
    public $timestamps = false; 

}
