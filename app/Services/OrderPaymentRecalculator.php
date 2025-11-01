<?php

namespace App\Services;

use App\Models\Order;
use App\Support\Payments;
use Illuminate\Support\Facades\DB;

class OrderPaymentRecalculator
{
    /**
     * Recalcula amount_paid, payment_status y paid_at de la orden.
     * Debe llamarse dentro de transacción cuando sea posible.
     */
    public function recalc(Order $order): void
    {
        // Bloqueo optimista: si llamas desde Observer ya vienes en transacción
        $order->refresh();

        // Sumar solo pagos válidos
        $sum = (float) $order->payments()
            ->whereIn('status', Payments::VALID_STATUSES_FOR_BALANCE)
            ->sum('amount');

        $total = (float) $order->total;

        $order->amount_paid = round($sum, 2);

        // Determinar estado global del pago
        if ($sum + Payments::EPSILON < 0.01) {
            $order->payment_status = 'unpaid';
            $order->paid_at = null;
        } elseif ($sum + Payments::EPSILON < $total) {
            // Hay algo pagado pero no completo
            // Si quieres mantener "pending_confirmation" hasta validar comprobante, cámbialo aquí.
            $order->payment_status = 'partially_paid';
            $order->paid_at = null;
        } else {
            // Igual o mayor que el total (con tolerancia)
            $order->payment_status = 'paid';
            // mantén la primera fecha en que se completó el pago
            if (empty($order->paid_at)) {
                $order->paid_at = now();
            }
        }

        $order->save();
    }

    /**
     * Versión segura con transacción + lock para llamadas externas.
     */
    public function recalcSafe(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            /** @var Order $order */
            $order = Order::where('id', $orderId)->lockForUpdate()->first();
            if (!$order) return;

            $this->recalc($order); // ya hace refresh/round
        });
    }
}
