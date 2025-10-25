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
}
