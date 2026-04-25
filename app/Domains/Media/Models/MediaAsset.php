<?php

namespace App\Domains\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model
{
    use SoftDeletes;

    protected $table = 'media_assets';

    protected $fillable = [
        'tenant_id',
        'path',
        'disk',
        'mime',
        'width',
        'height',
        'sha256',
        'alt_text',
        'created_by',
    ];
}

