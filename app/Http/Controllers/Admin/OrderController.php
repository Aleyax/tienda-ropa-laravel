<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Lista con filtros simples (?method=&pstatus=&status=)
    public function index(Request $request)
    {
        $q = Order::with(['user'])->latest();

        if ($m = $request->string('method')->toString())    $q->where('payment_method', $m);
        if ($ps = $request->string('pstatus')->toString())  $q->where('payment_status', $ps);
        if ($s = $request->string('status')->toString())    $q->where('status', $s);

        $orders = $q->paginate(12)->withQueryString();

        // catálogos para selects
        $methods  = ['transfer', 'cod', 'online'];
        $pstatuses = ['unpaid', 'pending_confirmation', 'cod_promised', 'authorized', 'paid', 'failed', 'partially_paid'];
        $statuses = ['new', 'confirmed', 'preparing', 'shipped', 'delivered', 'cancelled'];

        return view('admin.orders.index', compact('orders', 'methods', 'pstatuses', 'statuses'));
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
}
