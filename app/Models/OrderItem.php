<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'variant_id', 'qty', 'unit_price', 'amount', 'price_source'];
    protected $casts = ['qty' => 'int', 'unit_price' => 'float', 'amount' => 'float'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
