<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\OrderPaymentRecalculator;
use App\Support\Payments;

class OrderPaymentController extends Controller
{
    public function __construct(private OrderPaymentRecalculator $recalc)
    {
    }
    /**
     * Registrar un nuevo pago asociado a un pedido
     */
    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'method' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0.01',
            'provider_ref' => 'nullable|string|max:100',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096', // 4MB
        ]);

        DB::transaction(function () use ($request, $order, $data) {
            // 1) Subir comprobante (si viene)
            $evidenceUrl = null;
            if ($request->hasFile('evidence')) {
                // guarda en storage/app/public/order-payments/{order_id}/...
                $path = $request->file('evidence')
                    ->store("order-payments/{$order->id}", 'public');
                $evidenceUrl = Storage::disk('public')->url($path);
            }

            // 2) Crear pago
            /** @var OrderPayment $payment */
            $payment = $order->payments()->create([
                'method' => $data['method'],
                'amount' => $data['amount'],
                'status' => 'pending_confirmation', // por defecto
                'provider_ref' => $data['provider_ref'] ?? null,
                'evidence_url' => $evidenceUrl,
                'collected_by' => Auth::id(),
                'collected_at' => now(),
            ]);

            // 3) Recalcular estado global de la orden
            $this->recalc->recalc($order);
        });

        return back()->with('success', 'Pago registrado correctamente.');
    }

    /**
     * Cambiar el estado de un pago (solo admin o vendedor)
     */
    public function updateStatus(Request $request, OrderPayment $payment)
    {
        // autorización de rol
        abort_unless(Auth::user()?->hasAnyRole(['admin', 'vendedor']) ?? false, 403);

        $data = $request->validate([
            'status' => 'required|in:pending_confirmation,authorized,paid,failed,partially_paid,refunded',
        ]);

        DB::transaction(function () use ($payment, $data) {
            $payment->status = $data['status'];
            $payment->save();

            // tras cambiar el estado del pago, recalcular la orden
            $this->recalc->recalcSafe($payment->order_id);
        });

        return back()->with('success', "Estado del pago #{$payment->id} actualizado a {$data['status']}.");
    }
    public function deleteEvidence(Request $request, OrderPayment $payment)
    {
        abort_unless(Auth::user()?->hasAnyRole(['admin', 'vendedor']) ?? false, 403);

        if ($payment->evidence_url) {
            // evidence_url es la url pública; convertimos a path relativo si es de 'public'
            $publicPrefix = Storage::disk('public')->url('');
            if (str_starts_with($payment->evidence_url, $publicPrefix)) {
                $relative = substr($payment->evidence_url, strlen($publicPrefix));
                Storage::disk('public')->delete($relative);
            }
            $payment->evidence_url = null;
            $payment->save();
        }

        return back()->with('success', 'Comprobante eliminado.');
    }
}
