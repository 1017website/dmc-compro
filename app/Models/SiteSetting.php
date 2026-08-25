<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['setting_key', 'value'];

    public static function values(): array
    {
        return static::query()->pluck('value', 'setting_key')->all();
    }
}
