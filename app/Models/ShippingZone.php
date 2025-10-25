<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = ['name', 'districts', 'cod_enabled'];
    protected $casts = ['districts' => 'array', 'cod_enabled' => 'boolean'];
    public function rates()
    {
        return $this->hasMany(ShippingRate::class);
    }
    public static function findByDistrict(string $district): ?self
    {
        return static::whereJsonContains('districts', $district)->first();
    }
}
