<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];
    public $timestamps = true;

    // Método estático para acceder fácilmente
    public static function getValue(string $key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, $value)
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
