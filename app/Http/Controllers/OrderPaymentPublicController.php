<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\OrderPaymentRecalculator;

class OrderPaymentPublicController extends Controller
{
    public function create(Order $order)
    {
        // Solo el dueño del pedido puede ver el form
        abort_unless((int) $order->user_id === (int) Auth::id(), 403);
        // Si ya está paid, opcional: bloquear re-subidas
        // abort_if($order->payment_status === 'paid', 403);

        return view('shop.orders.payment-upload', compact('order'));
    }

    public function store(Request $request, Order $order, OrderPaymentRecalculator $recalc)
    {
        abort_unless((int) $order->user_id === (int) Auth::id(), 403);

        $data = $request->validate([
            'method' => 'required|string|max:50',      // Transferencia / Yape / Plin
            'amount' => 'required|numeric|min:0.01',
            'provider_ref' => 'nullable|string|max:100',
            'evidence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        // Subir evidencia
        $path = $request->file('evidence')->store("order-payments/{$order->id}", 'public');
        $url = asset('storage/' . $path);

        // Crear pago en pending_confirmation
        $order->payments()->create([
            'method' => $data['method'],
            'amount' => $data['amount'],
            'status' => 'pending_confirmation',
            'provider_ref' => $data['provider_ref'] ?? null,
            'evidence_url' => $url,
            'collected_by' => Auth::id(),
            'collected_at' => now(),
        ]);

        // Recalcular estado global del pedido
        $recalc->recalc($order);

        // Redirige al detalle del pedido del cliente (ajusta a tu ruta real)
        return redirect()
            ->route('admin.orders.show', $order)  // o tu “thanks”
            ->with('success', 'Comprobante enviado. Revisaremos tu pago pronto.');
    }
}
