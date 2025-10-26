<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use App\Models\OrderItem;
class OrderController extends Controller
{
    // Lista con filtros simples (?method=&pstatus=&status=)
    public function index(Request $request)
    {
        $q = Order::with(['user'])->latest();

        // Filtros existentes
        if ($m = $request->string('method')->toString())    $q->where('payment_method', $m);
        if ($ps = $request->string('pstatus')->toString())  $q->where('payment_status', $ps);
        if ($s = $request->string('status')->toString())    $q->where('status', $s);

        // 🔎 Filtros nuevos
        if ($from = $request->date('from')) $q->whereDate('created_at', '>=', $from);
        if ($to   = $request->date('to'))   $q->whereDate('created_at', '<=', $to);
        if ($email = $request->string('email')->toString()) {
            $q->whereHas('user', fn($uq) => $uq->where('email', 'like', "%{$email}%"));
        }

        $orders   = $q->paginate(20)->withQueryString();

        // Totales de la página y del filtro (útil para control rápido)
        $pageTotal   = $orders->sum('total');
        $filterTotal = (clone $q)->sum('total'); // ojo: clonar ANTES del paginate

        $methods   = ['transfer', 'cod', 'online'];
        $pstatuses = ['unpaid', 'pending_confirmation', 'cod_promised', 'authorized', 'paid', 'failed', 'partially_paid'];
        $statuses  = ['new', 'confirmed', 'preparing', 'shipped', 'delivered', 'cancelled'];

        return view(
            'admin.orders.index',
            compact('orders', 'methods', 'pstatuses', 'statuses', 'pageTotal', 'filterTotal')
        );
    }

    // Detalle
    public function show(Order $order)
    {
        $order->load(['items' => function ($q) {
            $q->with(['order']);
        }, 'user', 'payments']);
        // (Opcional) eager para variant/product si quieres:
        $order->load(['items' => fn($q) => $q->with(['order'])]);
        return view('admin.orders.show', compact('order'));
    }

    // Cambiar estado del pedido
    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:new,confirmed,preparing,shipped,delivered,cancelled'
        ]);

        $order->update(['status' => $data['status']]);
        return back()->with('success', "Estado de pedido actualizado a {$data['status']}.");
    }

    // Cambiar estado del pago
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_status' => 'required|in:unpaid,pending_confirmation,cod_promised,authorized,paid,failed,partially_paid'
        ]);

        $order->update([
            'payment_status' => $data['payment_status'],
            'paid_at'        => $data['payment_status'] === 'paid' ? now() : null,
        ]);

        return back()->with('success', "Estado de pago actualizado a {$data['payment_status']}.");
    }
    public function bulkStatus(Request $request)
    {
        $data = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:orders,id',
            'status' => 'required|in:new,confirmed,preparing,shipped,delivered,cancelled',
        ]);

        Order::whereIn('id', $data['ids'])->update(['status' => $data['status']]);
        return back()->with('success', 'Estados de pedido actualizados.');
    }
    public function bulkPayStatus(Request $request)
    {
        $data = $request->validate([
            'ids'            => 'required|array|min:1',
            'ids.*'          => 'integer|exists:orders,id',
            'payment_status' => 'required|in:unpaid,pending_confirmation,cod_promised,authorized,paid,failed,partially_paid',
        ]);

        $update = ['payment_status' => $data['payment_status']];
        if ($data['payment_status'] === 'paid') $update['paid_at'] = now();
        else $update['paid_at'] = null;

        Order::whereIn('id', $data['ids'])->update($update);
        return back()->with('success', 'Estados de pago actualizados.');
    }
    public function pickItem(Request $request, Order $order, OrderItem $item)
    {
        // Valida que el ítem pertenezca al pedido
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        $data = $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $qtyToPick = (int)$data['qty'];

        return DB::transaction(function () use ($order, $item, $qtyToPick) {
            $item->refresh(); // estado más reciente
            $variant = ProductVariant::where('id', $item->variant_id)->lockForUpdate()->first();

            if (!$variant) {
                return back()->with('error', 'Variante no encontrada.');
            }

            // Reglas:
            // 1) No puedes pickear más de lo pendiente en backorder
            if ($qtyToPick > $item->backorder_qty) {
                return back()->with('error', 'No puedes pickear más que el backorder pendiente.');
            }

            // 2) No puedes pickear más del stock disponible
            if ($qtyToPick > $variant->stock) {
                return back()->with('error', "Stock insuficiente. Disponible: {$variant->stock}.");
            }

            // Descuenta stock real y reduce backorder
            $variant->decrement('stock', $qtyToPick);
            $item->decrement('backorder_qty', $qtyToPick);

            // (Opcional) si quieres reflejar montos/estado en order según progreso, puedes hacerlo aquí.

            return back()->with('success', "Pick registrado por {$qtyToPick} unidades de {$item->sku}.");
        });
    }

    public function unpickItem(Request $request, Order $order, OrderItem $item)
    {
        // Valida que el ítem pertenezca al pedido
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        $data = $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $qtyToUnpick = (int)$data['qty'];

        return DB::transaction(function () use ($order, $item, $qtyToUnpick) {
            $variant = ProductVariant::where('id', $item->variant_id)->lockForUpdate()->first();

            if (!$variant) {
                return back()->with('error', 'Variante no encontrada.');
            }

            // Reglas: "unpick" significa revertir un pick ya hecho (reponer stock y aumentar backorder)
            // No deberías unpickear más allá del total original pedido (qty), ni dejar backorder por encima del qty
            $maxPending = $item->qty - $item->backorder_qty; // esto es lo que ya pickeaste antes
            if ($qtyToUnpick > $maxPending) {
                return back()->with('error', 'No puedes revertir más de lo que ya está pickeado.');
            }

            // Reponer stock y aumentar backorder
            $variant->increment('stock', $qtyToUnpick);
            $item->increment('backorder_qty', $qtyToUnpick);

            return back()->with('success', "Unpick registrado por {$qtyToUnpick} unidades de {$item->sku}.");
        });
    }

    public function export(Request $request): StreamedResponse
    {
        // Reutiliza EXACTAMENTE los mismos filtros que index()
        $q = Order::with('user')->latest();
        if ($m = $request->string('method')->toString())    $q->where('payment_method', $m);
        if ($ps = $request->string('pstatus')->toString())  $q->where('payment_status', $ps);
        if ($s = $request->string('status')->toString())    $q->where('status', $s);
        if ($from = $request->date('from')) $q->whereDate('created_at', '>=', $from);
        if ($to   = $request->date('to'))   $q->whereDate('created_at', '<=', $to);
        if ($email = $request->string('email')->toString()) {
            $q->whereHas('user', fn($uq) => $uq->where('email', 'like', "%{$email}%"));
        }
        $filename = 'orders_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            // Cabeceras
            fputcsv($out, ['order_id', 'fecha', 'cliente_nombre', 'cliente_email', 'payment_method', 'payment_status', 'status', 'subtotal', 'tax', 'total']);

            // Chunk para no explotar memoria
            $q->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $o) {
                    fputcsv($out, [
                        $o->id,
                        $o->created_at?->format('Y-m-d H:i'),
                        $o->user?->name,
                        $o->user?->email,
                        $o->payment_method,
                        $o->payment_status,
                        $o->status,
                        number_format($o->subtotal, 2, '.', ''),
                        number_format($o->tax, 2, '.', ''),
                        number_format($o->total, 2, '.', ''),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
