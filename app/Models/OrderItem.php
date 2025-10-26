<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'name',
        'sku',
        'unit_price',
        'qty',
        'backorder_qty',
        'amount',
    ];
    protected $casts = ['qty' => 'int', 'unit_price' => 'float', 'amount' => 'float'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
