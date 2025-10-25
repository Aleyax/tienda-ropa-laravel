<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $fillable = ['shipping_zone_id', 'name', 'price', 'eta_days'];
    protected $casts = ['price' => 'float', 'eta_days' => 'int'];
    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
