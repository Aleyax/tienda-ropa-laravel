<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class PaymentLog extends Model
{
    protected $fillable = [
        'order_payment_id',
        'order_id',
        'actor_id',
        'event',
        'old_payload',
        'new_payload',
        'payload',
        'ip',
        'user_id',
        'meta',
    ];

    protected $casts = [
        'old_payload' => 'array',
        'new_payload' => 'array',
        'payload' => 'array',
        'meta' => 'array',
    ];
    public function scopeForOrder($q, int $orderId)
    {
        return $q->where('order_id', $orderId);
    }

    public function scopeEventLike($q, ?string $event)
    {
        if ($event)
            $q->where('event', 'like', "%{$event}%");
        return $q;
    }


    public function scopeByActor($q, ?int $actorId)
    {
        return $actorId ? $q->where('actor_id', $actorId) : $q;
    }
    public function scopeBetween($q, ?string $from, ?string $to)
    {
        if ($from)
            $q->where('created_at', '>=', $from . ' 00:00:00');
        if ($to)
            $q->where('created_at', '<=', $to . ' 23:59:59');
        return $q;
    }

    public function payment()
    {
        return $this->belongsTo(OrderPayment::class, 'order_payment_id');
    }
    // --- Relaciones útiles ---
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function orderPayment()
    {
        return $this->belongsTo(OrderPayment::class, 'order_payment_id');
    }
    // PaymentLog.php





}
