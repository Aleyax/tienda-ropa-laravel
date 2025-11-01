<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'payment_method', 'payment_status', 'status', 'subtotal', 'tax', 'total', 'cod_details', 'voucher_url', 'paid_at'];
    protected $casts = [
        'is_priority' => 'boolean',
        'priority_level' => 'integer',
        'paid_at' => 'datetime',
    ];
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
    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }
    public function shippingZone()
    {
        return $this->belongsTo(ShippingZone::class);
    }
    public function shippingRate()
    {
        return $this->belongsTo(ShippingRate::class);
    }
    public function scopeType($q, ?string $type)
    {
        if ($type === 'retail' || $type === 'wholesale') {
            $q->where('order_type', $type);
        }
        return $q;
    }
    public function scopePendingPicking($q)
    {
        return $q->whereHas('items', fn($iq) => $iq->where('backorder_qty', '>', 0));
    }
    public function logs()
    {
        return $this->hasMany(\App\Models\OrderLog::class)->latest();
    }

}
