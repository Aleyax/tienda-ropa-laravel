<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderPaymentController extends Controller
{
    /**
     * Registrar un nuevo pago asociado a un pedido
     */
    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'method'       => 'required|string|max:100',
            'amount'       => 'required|numeric|min:0.01',
            'provider_ref' => 'nullable|string|max:255',
            'evidence'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $evidenceUrl = null;
        if ($request->hasFile('evidence')) {
            $path = $request->file('evidence')->store('payments', 'public');
            $evidenceUrl = Storage::url($path);
        }

        OrderPayment::create([
            'order_id'      => $order->id,
            'method'        => $data['method'],
            'amount'        => $data['amount'],
            'provider_ref'  => $data['provider_ref'] ?? null,
            'evidence_url'  => $evidenceUrl,
            'status'        => 'pending_confirmation',
            'collected_by'  => Auth::id(),
            'collected_at'  => now(),
        ]);

        return back()->with('success', 'Pago registrado correctamente. Pendiente de confirmación.');
    }

    /**
     * Cambiar el estado de un pago (solo admin o vendedor)
     */
    public function updateStatus(Request $request, OrderPayment $payment)
    {
        $request->validate([
            'status' => 'required|string|in:pending_confirmation,authorized,paid,failed,partially_paid,refunded',
        ]);

        $payment->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'Estado del pago actualizado correctamente.');
    }
}
