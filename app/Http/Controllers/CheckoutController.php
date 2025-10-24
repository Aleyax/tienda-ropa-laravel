<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function show(PricingService $pricing)
    {
        $cart = session('cart', []);
        if (empty($cart)) return redirect()->route('cart.index');

        // Recalcular totales
        $lines = [];
        $subtotal = 0;
        foreach ($cart as $line) {
            $product = Product::find($line['product_id']);
            $variant = ProductVariant::find($line['variant_id']);
            [$price, $source] = method_exists($pricing, 'priceForWithSource')
                ? $pricing->priceForWithSource(Auth::user(), $product, $variant->id)
                : [$pricing->priceFor(Auth::user(), $product, $variant->id), 'auto'];

            $amount = $price * $line['qty'];
            $subtotal += $amount;
            $lines[] = compact('product', 'variant') + [
                'qty' => $line['qty'],
                'price' => $price,
                'amount' => $amount,
                'source' => $source
            ];
        }
        $igv = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        return view('checkout.show', compact('lines', 'subtotal', 'igv', 'total'));
    }

    public function place(Request $request, PricingService $pricing)
    {
        $data = $request->validate([
            'payment_method' => 'required|in:transfer,cod,online',
            'cod_pay_type'   => 'nullable|in:cash,yape,plin',
            'cod_change'     => 'nullable|string|max:20',
        ]);

        $cart = session('cart', []);
        if (empty($cart)) return redirect()->route('cart.index');

        // Totales
        $subtotal = $igv = $total = 0;
        $items = [];
        foreach ($cart as $line) {
            $product = Product::findOrFail($line['product_id']);
            $variant = ProductVariant::findOrFail($line['variant_id']);
            [$price, $source] = method_exists($pricing, 'priceForWithSource')
                ? $pricing->priceForWithSource(Auth::user(), $product, $variant->id)
                : [$pricing->priceFor(Auth::user(), $product, $variant->id), 'auto'];
            $amount = $price * $line['qty'];
            $subtotal += $amount;
            $items[] = compact('product', 'variant', 'price', 'source') + ['qty' => $line['qty'], 'amount' => $amount];
        }
        $igv = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        // Crear Order
        $order = Order::create([
            'user_id'        => Auth::id(),
            'payment_method' => $data['payment_method'],
            'payment_status' => $data['payment_method'] === 'transfer' ? 'pending_confirmation'
                : ($data['payment_method'] === 'cod' ? 'cod_promised' : 'authorized'),
            'status'         => 'new',
            'subtotal'       => $subtotal,
            'tax'            => $igv,
            'total'          => $total,
            'cod_details'    => $data['payment_method'] === 'cod'
                ? ['pay_type' => $data['cod_pay_type'], 'change' => $data['cod_change']]
                : null,
        ]);

        foreach ($items as $it) {
            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $it['product']->id,
                'variant_id'   => $it['variant']->id,
                'qty'          => $it['qty'],
                'unit_price'   => $it['price'],
                'amount'       => $it['amount'],
                'price_source' => $it['source'],
            ]);
        }

        // Registrar pago (inicial)
        OrderPayment::create([
            'order_id'    => $order->id,
            'method'      => $data['payment_method'],
            'amount'      => $total,
            'status'      => $data['payment_method'] === 'online' ? 'pending' : 'pending',
        ]);

        // Simulación de cada método
        if ($data['payment_method'] === 'online') {
            // Sandbox: marcamos como pagado inmediato (simulado)
            $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
            $order->payments()->latest()->first()->update(['status' => 'validated', 'provider_ref' => 'SIMULATED-OK']);
        }

        // Vaciar carrito
        session()->forget('cart');

        return redirect()->route('checkout.show')->with('success', 'Pedido creado #' . $order->id . ' (estado pago: ' . $order->payment_status . ')');
    }

    public function uploadVoucher(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'voucher'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $order = Order::findOrFail($data['order_id']);
        if ($order->payment_method !== 'transfer') {
            return back()->with('error', 'Este pedido no es de transferencia.');
        }


        $path = $request->file('voucher')->store('vouchers', 'public');
        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk('public');
        $url = $storage->url($path);

        $order->update([
            'voucher_url'    => $url,
            'payment_status' => 'pending_confirmation',
        ]);

        $order->payments()->create([
            'method'       => 'transfer',
            'amount'       => $order->total,
            'status'       => 'pending',
            'evidence_url' => $url,
        ]);

        return back()->with('success', 'Voucher subido. Será validado por Operaciones.');
    }
}
