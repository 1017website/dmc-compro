<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    protected $fillable = [
        'content_key', 'group_name', 'label', 'type', 'value_id', 'value_en', 'value_zh',
    ];
}
