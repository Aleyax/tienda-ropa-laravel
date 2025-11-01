<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'payment_method',
        'payment_status',
        'status',
        'subtotal',
        'tax',
        'total',
        'cod_details',
        'voucher_url',
        'paid_at',
        'is_priority',
        'priority_level',
        'shipping_amount',
        'shipping_estimated',
        'shipping_actual',
        'shipping_settlement_status',
    ];
    protected $casts = [
        'is_priority' => 'boolean',
        'priority_level' => 'integer',
        'paid_at' => 'datetime',
        'shipping_amount' => 'decimal:2',
        'shipping_estimated' => 'decimal:2',
        'shipping_actual' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
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
    // app/Models/Order.php

    public function recomputePaymentStatus(): array
    {
        // Sumas por estado
        $sumPaid = (float) $this->payments()->whereIn('status', ['paid', 'authorized'])->sum('amount');
        $sumPending = (float) $this->payments()->whereIn('status', ['pending_confirmation'])->sum('amount');
        $sumFailed = (float) $this->payments()->whereIn('status', ['failed', 'refunded'])->sum('amount');

        $total = (float) ($this->total ?? 0);
        $remaining = max(0, round($total - $sumPaid, 2));
        $progress = $total > 0 ? min(100, round(($sumPaid / $total) * 100)) : 0;

        // Reglas de estado global
        // Prioridad: pago completo > pago parcial > pendiente confirmación > unpaid
        if ($total > 0 && $sumPaid >= $total) {
            $computed = 'paid';
        } elseif ($sumPaid > 0) {
            $computed = 'partially_paid';
        } elseif ($sumPending > 0) {
            $computed = 'pending_confirmation';
        } else {
            $computed = 'unpaid';
        }

        return [
            'computed_status' => $computed,
            'sum_paid' => $sumPaid,
            'sum_pending' => $sumPending,
            'sum_failed' => $sumFailed,
            'total' => $total,
            'remaining' => $remaining,
            'progress_pct' => $progress,
        ];
    }

    /**
     * Sincroniza payment_status y paid_at con los pagos registrados.
     * Retorna el resumen para reusar en la vista.
     */
    public function syncPaymentStatus(bool $save = true): array
    {
        $res = $this->recomputePaymentStatus();

        // Ajustar paid_at coherentemente
        $newStatus = $res['computed_status'];
        $dirty = false;

        if ($this->payment_status !== $newStatus) {
            $this->payment_status = $newStatus;
            $dirty = true;
        }

        if ($newStatus === 'paid') {
            // Si está completamente pagado, setear paid_at si no existe
            if (is_null($this->paid_at)) {
                $this->paid_at = now();
                $dirty = true;
            }
        } else {
            // Si deja de estar paid, paid_at debe limpiarse
            if (!is_null($this->paid_at)) {
                $this->paid_at = null;
                $dirty = true;
            }
        }

        if ($save && $dirty) {
            $this->save();
        }

        return $res;
    }

}
