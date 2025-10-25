<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'color_id', 'size_id', 'sku', 'barcode', 'stock', 'price_base'];
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
    public function color()
    {
        return $this->belongsTo(\App\Models\Color::class);
    }
    public function size()
    {
        return $this->belongsTo(\App\Models\Size::class);
    }
}
