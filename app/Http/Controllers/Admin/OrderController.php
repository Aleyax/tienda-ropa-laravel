<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
