<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderPaymentRecalculator
{
    /**
     * Recalcula el estado de pago del pedido en base a sus pagos asociados.
     */
    public function recalc(Order $order): void
    {
        DB::transaction(function () use ($order) {

            // Sumar pagos según su estado
            $sumPaid = (float) $order->payments()
                ->whereIn('status', ['paid', 'authorized'])
                ->sum('amount');

            $sumPending = (float) $order->payments()
                ->where('status', 'pending_confirmation')
                ->sum('amount');

            $sumFailed = (float) $order->payments()
                ->whereIn('status', ['failed', 'refunded'])
                ->sum('amount');

            $total = (float) $order->total;

            // Determinar nuevo estado
            $newStatus = match (true) {
                $sumPaid >= $total && $total > 0 => 'paid',
                $sumPaid > 0 && $sumPaid < $total => 'partially_paid',
                $sumPending > 0 => 'pending_confirmation',
                $sumFailed > 0 => 'failed',
                default => 'unpaid',
            };

            $old = $order->payment_status;

            // Solo actualizar si cambió
            if ($newStatus !== $old) {
                $order->payment_status = $newStatus;
                $order->paid_at = $newStatus === 'paid' ? now() : null;
                $order->save();

                // Log opcional
                Log::info("[OrderPaymentRecalc] Order #{$order->id}: {$old} → {$newStatus}");
            }
            if ($newStatus !== $old) {
                $order->payment_status = $newStatus;
                $order->paid_at = $newStatus === 'paid' ? now() : null;
                $order->save();

                PaymentLogger::log(
                    event: 'order_payment_status_recalc',
                    payload: [
                        'old' => ['payment_status' => $old],
                        'new' => ['payment_status' => $newStatus],
                    ],
                    orderId: $order->id,
                    meta: [
                        'reason' => 'auto_recalc',
                        'by' => 'system',
                    ]
                );
            }
        });
    }

    /**
     * Recalcular de forma segura por ID (por si no tienes instancia cargada).
     */
    public function recalcSafe(int $orderId): void
    {
        if ($order = Order::find($orderId)) {
            $this->recalc($order);
        }
    }
}
