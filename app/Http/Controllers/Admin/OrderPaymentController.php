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
use App\Services\PaymentLogger;
use Illuminate\Support\Str;
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
                $path = $request->file('evidence')->store("order-payments/{$order->id}", 'public');

                // Evitamos url() del Filesystem y construimos la pública nosotros:
                $evidenceUrl = asset('storage/' . $path);
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
            PaymentLogger::log(
                event: 'payment_created',
                payload: [
                    'new' => [
                        'id' => $payment->id,
                        'order_id' => $payment->order_id,
                        'method' => $payment->method,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'provider_ref' => $payment->provider_ref,
                        'evidence_url' => $payment->evidence_url,
                        'collected_by' => $payment->collected_by,
                        'collected_at' => optional($payment->collected_at)?->toDateTimeString(),
                    ],
                ],
                orderId: $order->id,
                orderPaymentId: $payment->id,
                meta: [
                    'reason' => 'user_submit_payment',
                    'uploaded' => (bool) $evidenceUrl,
                    'evidence_name' => $request->file('evidence')?->getClientOriginalName(),
                    'evidence_mime' => $request->file('evidence')?->getMimeType(),
                    'evidence_size' => $request->file('evidence')?->getSize(),
                ]
            );
        });

        //return back()->with('success', 'Pago registrado correctamente.');
        return redirect()->route('admin.orders.show', $order)->with('success', 'Pago registrado correctamente.');

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
            $before = $payment->only(['status', 'amount', 'method', 'provider_ref', 'evidence_url']);
            $payment->status = $data['status'];
            $payment->save();

            // tras cambiar el estado del pago, recalcular la orden
            $this->recalc->recalcSafe($payment->order_id);

            PaymentLogger::log(
                event: 'payment_status_updated',
                payload: [
                    'old' => $before,
                    'new' => $payment->only(['status', 'amount', 'method', 'provider_ref', 'evidence_url']),
                ],
                orderId: $payment->order_id,
                orderPaymentId: $payment->id,
                meta: [
                    'reason' => 'admin_or_seller_update',
                ]
            );
        });

        return back()->with('success', "Estado del pago #{$payment->id} actualizado a {$data['status']}.");
    }
    public function deleteEvidence(Request $request, OrderPayment $payment)
    {
        abort_unless(Auth::user()?->hasAnyRole(['admin', 'vendedor']) ?? false, 403);

        $before = $payment->only(['evidence_url']);

        if ($payment->evidence_url) {
            // 1) Extraer el path del URL (o dejar tal cual si ya es relativo)
            $pathFromUrl = parse_url($payment->evidence_url, PHP_URL_PATH) ?: $payment->evidence_url;
            // e.g. "/storage/order-payments/74/archivo.jpg"  ó  "order-payments/74/archivo.jpg"

            // 2) Quitar el prefijo "/storage/" para obtener la ruta relativa dentro del disco 'public'
            if (Str::startsWith($pathFromUrl, '/storage/')) {
                $relative = ltrim(Str::after($pathFromUrl, '/storage/'), '/'); // "order-payments/74/archivo.jpg"
            } else {
                // Puede que ya venga "order-payments/74/archivo.jpg"
                $relative = ltrim($pathFromUrl, '/');
            }

            // 3) Intentar borrar si existe
            if ($relative && Storage::disk('public')->exists($relative)) {
                Storage::disk('public')->delete($relative);
            }

            // 4) Limpiar el campo y guardar
            $payment->evidence_url = null;
            $payment->save();

            // 5) Log
            PaymentLogger::log(
                event: 'payment_evidence_deleted',
                payload: [
                    'old' => $before,
                    'new' => ['evidence_url' => null],
                ],
                orderId: $payment->order_id,
                orderPaymentId: $payment->id,
                meta: [
                    'reason' => 'admin_or_seller_delete_evidence',
                ]
            );
        }

        return back()->with('success', 'Comprobante eliminado.');
    }

    // app/Http/Controllers/Admin/OrderPaymentController.php

    public function destroy(Request $request, OrderPayment $payment)
    {
        abort_unless(\Auth::user()?->hasAnyRole(['admin', 'vendedor']) ?? false, 403);

        $before = $payment->only(['status', 'amount', 'method', 'provider_ref', 'evidence_url']);
        $orderId = (int) $payment->order_id;

        DB::transaction(function () use ($payment, $before) {
            $payment->delete();

            PaymentLogger::log(
                event: 'payment_soft_deleted',
                payload: ['old' => $before, 'new' => ['deleted_at' => now()->toDateTimeString()]],
                orderId: $payment->order_id,
                orderPaymentId: $payment->id,
                meta: ['reason' => 'admin_or_seller_soft_delete']
            );

            app(OrderPaymentRecalculator::class)->recalcSafe($payment->order_id);
        });

        return back()->with('success', "Pago #{$payment->id} eliminado (recuperable).");
    }

    public function restore(Request $request, int $id)
    {
        abort_unless(\Auth::user()?->hasAnyRole(['admin', 'vendedor']) ?? false, 403);

        $payment = OrderPayment::withTrashed()->findOrFail($id);
        DB::transaction(function () use ($payment) {
            $payment->restore();

            PaymentLogger::log(
                event: 'payment_restored',
                payload: ['old' => ['deleted_at' => now()->toDateTimeString()], 'new' => ['deleted_at' => null]],
                orderId: $payment->order_id,
                orderPaymentId: $payment->id,
                meta: ['reason' => 'admin_or_seller_restore']
            );

            app(OrderPaymentRecalculator::class)->recalcSafe($payment->order_id);
        });

        return back()->with('success', "Pago #{$payment->id} restaurado.");
    }

    public function forceDelete(Request $request, int $id)
    {
        abort_unless(\Auth::user()?->hasAnyRole(['admin']) ?? false, 403); // definitivo solo admin

        $payment = OrderPayment::withTrashed()->findOrFail($id);

        \DB::transaction(function () use ($payment) {
            $before = $payment->only(['status', 'amount', 'method', 'provider_ref', 'evidence_url']);
            $orderId = (int) $payment->order_id;

            // si quieres, también borra el archivo evidencia si existe y está en public
            if ($payment->evidence_url) {
                $publicBase = rtrim(\Storage::url(''), '/'); // ej: /storage
                $baseHost = rtrim(config('app.url'), '/');   // ej: http://localhost
                // Soportar URL absoluta o relativa
                $url = $payment->evidence_url;
                if (str_starts_with($url, $baseHost)) {
                    $url = substr($url, strlen($baseHost));
                }
                if (str_starts_with($url, $publicBase)) {
                    $relative = ltrim(substr($url, strlen($publicBase)), '/');
                    \Storage::disk('public')->delete($relative);
                }
            }

            $payment->forceDelete();

            PaymentLogger::log(
                event: 'payment_force_deleted',
                payload: ['old' => $before, 'new' => null],
                orderId: $orderId,
                orderPaymentId: null,
                meta: ['reason' => 'admin_force_delete']
            );

            app(OrderPaymentRecalculator::class)->recalcSafe($orderId);
        });

        return back()->with('success', "Pago eliminado definitivamente.");
    }

}
