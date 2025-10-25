<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function show(Order $order)
    {
        $order->load([
            'items.product',
            'items.variant',
            'shippingAddress',
            'shippingZone',
            'shippingRate',
        ]);

        // diferencia actual (si ya hay shipping_actual)
        $diff = null;
        if (!is_null($order->shipping_actual)) {
            $diff = round($order->shipping_amount - $order->shipping_actual, 2); // + => refund, - => charge
        }

        return view('admin.orders.show', compact('order', 'diff'));
    }

    public function saveShippingActual(Request $request, Order $order)
    {
        // Para pickup no hay liquidación
        if ($order->shipping_mode === 'pickup') {
            return back()->with('error', 'Recojo en tienda: no aplica costo de envío.');
        }

        $data = $request->validate([
            'shipping_actual' => 'required|numeric|min:0',
        ]);

        $order->shipping_actual = (float) $data['shipping_actual'];
        $order->save();

        return back()->with('success', 'Costo real de envío guardado.');
    }

    public function settlementRefund(Request $request, Order $order)
    {
        if ($order->shipping_mode === 'pickup') {
            return back()->with('error', 'Recojo en tienda: no aplica liquidación.');
        }
        if (is_null($order->shipping_actual)) {
            return back()->with('error', 'Primero registra el costo real de envío.');
        }
        $diff = round($order->shipping_amount - $order->shipping_actual, 2);
        if ($diff <= 0) {
            return back()->with('error', 'No hay monto a devolver.');
        }

        OrderPayment::create([
            'order_id' => $order->id,
            'method'   => 'manual',    // o 'transfer' si lo defines
            'kind'     => 'refund',    // << clave para identificar devolución
            'amount'   => $diff,
            'status'   => 'completed',
            'notes'    => 'Liquidación envío (reembolso diferencia)',
        ]);

        $order->shipping_settlement_status = 'settled';
        $order->settled_at = now();
        $order->save();

        return back()->with('success', "Reembolso registrado por S/ {$diff}.");
    }

    public function settlementCharge(Request $request, Order $order)
    {
        if ($order->shipping_mode === 'pickup') {
            return back()->with('error', 'Recojo en tienda: no aplica liquidación.');
        }
        if (is_null($order->shipping_actual)) {
            return back()->with('error', 'Primero registra el costo real de envío.');
        }
        $diff = round($order->shipping_actual - $order->shipping_amount, 2);
        if ($diff <= 0) {
            return back()->with('error', 'No hay monto adicional por cobrar.');
        }

        OrderPayment::create([
            'order_id' => $order->id,
            'method'   => 'manual',    // o el método real de cobro
            'kind'     => 'charge',    // << cargo adicional
            'amount'   => $diff,
            'status'   => 'completed',
            'notes'    => 'Liquidación envío (cargo adicional)',
        ]);

        $order->shipping_settlement_status = 'settled';
        $order->settled_at = now();
        $order->save();

        return back()->with('success', "Cargo adicional registrado por S/ {$diff}.");
    }
}
