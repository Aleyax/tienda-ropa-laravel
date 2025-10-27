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
        $row = static::query()->where('key', $key)->first();
        if (!$row) return $default;
        return is_numeric($row->value) ? 0 + $row->value : $row->value;
    }

    public static function setValue(string $key, $value)
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string)$value]);
    }
}
