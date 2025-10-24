<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'payment_method', 'payment_status', 'status', 'subtotal', 'tax', 'total', 'cod_details', 'voucher_url', 'paid_at'];
    protected $casts = ['cod_details' => 'array', 'subtotal' => 'float', 'tax' => 'float', 'total' => 'float', 'paid_at' => 'datetime'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
