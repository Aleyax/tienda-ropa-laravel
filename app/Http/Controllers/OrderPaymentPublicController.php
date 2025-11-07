<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\OrderPaymentRecalculator;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class OrderPaymentPublicController extends Controller
{
    use AuthorizesRequests; // Add this line to include the trait
    public function create(Order $order)
    {
        $this->authorize('uploadPayment', $order);

        $order->load('payments');

        return view('shop.orders.payment-upload', compact('order'));
    }

    public function store(Request $request, Order $order, \App\Services\OrderPaymentRecalculator $recalc)
    {
        $this->authorize('uploadPayment', $order);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|max:50',
            'provider_ref' => 'nullable|string|max:100',
            'evidence' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $path = $request->file('evidence')->store("order-payments/{$order->id}", 'public');
        $url = asset("storage/{$path}");

        $order->payments()->create([
            'amount' => $data['amount'],
            'method' => $data['method'],
            'provider_ref' => $data['provider_ref'] ?? null,
            'evidence_url' => $url,
            'status' => 'pending_confirmation',
            'collected_by' => $order->user_id,
            'collected_at' => now(),
        ]);

        $recalc->recalc($order);

        return redirect()
            ->route('checkout.thanks', $order)
            ->with('success', 'Tu comprobante fue enviado. Estamos verificando tu pago.');
    }

}
