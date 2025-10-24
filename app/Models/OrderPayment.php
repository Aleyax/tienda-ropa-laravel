<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    protected $fillable = ['order_id', 'method', 'amount', 'status', 'provider_ref', 'evidence_url', 'collected_by', 'collected_at'];
    protected $casts = ['amount' => 'float', 'collected_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
