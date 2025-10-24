<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceListItem extends Model
{
    protected $fillable = ['price_list_id', 'product_id', 'variant_id', 'price'];

    public function priceList()
    {
        return $this->belongsTo(\App\Models\PriceList::class, 'price_list_id');
    }
}
