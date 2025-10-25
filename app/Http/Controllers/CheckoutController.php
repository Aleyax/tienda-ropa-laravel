<?php

namespace App\Http\Controllers;

use App\Models\User;
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
use App\Models\Address;
use Illuminate\Support\Str;
use App\Models\ShippingZone;

class CheckoutController extends Controller
{
    public function show(Request $request, PricingService $pricing)
    {
        $cart = session('cart', []);
        if (empty($cart)) return redirect()->route('cart.index');

        // --- Recalcular líneas y totales (tu lógica actual) ---
        $lines = [];
        $subtotal = 0;
        foreach ($cart as $line) {
            $product = Product::find($line['product_id']);
            $variant = ProductVariant::find($line['variant_id']);

            [$price, $source] = method_exists($pricing, 'priceForWithSource')
                ? $pricing->priceForWithSource(Auth::user(), $product, $variant->id)
                : [$pricing->priceFor(Auth::user(), $product, $variant->id), 'auto'];

            $amount   = $price * $line['qty'];
            $subtotal += $amount;

            $lines[] = compact('product', 'variant') + [
                'qty'    => $line['qty'],
                'price'  => $price,
                'amount' => $amount,
                'source' => $source,
            ];
        }
        $igv   = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        // --- Nuevos: Envío / Recojo ---
        /** @var User|null $user */
        $user      = Auth::user();
        $addresses = $user ? $user->addresses()->orderByDesc('is_default')->get() : collect();

        // pickup | deposit | to_be_quoted
        $shippingMode = $request->input('shipping_mode', 'pickup');
        $addressId    = (int) $request->input('shipping_address_id');

        $shippingAmount     = 0.0;     // lo que se cobra HOY
        $shippingEstimated  = null;    // estimado de la zona (solo referencia)
        $shippingZone       = null;
        $shippingRate       = null;
        $depositDefault     = 50.00;   // monto sugerido de depósito

        if ($shippingMode === 'deposit') {
            // Dirección elegida o la predeterminada
            $address = $addresses->firstWhere('id', $addressId) ?? $addresses->first();

            if ($address) {
                $shippingZone = ShippingZone::findByDistrict($address->district);
                $shippingRate = $shippingZone?->rates()->orderBy('price')->first();
                $shippingEstimated = $shippingRate?->price ?? 0.0;
            }

            // Lo que cobras hoy por envío (editable en la vista)
            $shippingAmount = (float) $request->input('shipping_deposit', $depositDefault);
        } elseif ($shippingMode === 'to_be_quoted') {
            $shippingAmount    = 0.0;   // hoy no cobras envío
            $shippingEstimated = null;  // se definirá luego
        } else { // pickup
            $shippingAmount    = 0.0;
            $shippingEstimated = 0.0;
        }

        $grandTotal = round($total + $shippingAmount, 2);

        return view('checkout.show', compact(
            'lines',
            'subtotal',
            'igv',
            'total',
            'addresses',
            'shippingMode',
            'addressId',
            'shippingZone',
            'shippingRate',
            'shippingEstimated',
            'shippingAmount',
            'grandTotal',
            'depositDefault'
        ));
    }
    public function place(Request $request, PricingService $pricing)
    {
        $user = Auth::user();

        // 1) Recalcular carrito (idéntico a show)
        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Tu carrito está vacío.');
        }

        $lines = [];
        $subtotal = 0;
        foreach ($cart as $line) {
            $product = Product::findOrFail($line['product_id']);
            $variant = ProductVariant::findOrFail($line['variant_id']);

            [$price] = method_exists($pricing, 'priceForWithSource')
                ? $pricing->priceForWithSource($user, $product, $variant->id)
                : [$pricing->priceFor($user, $product, $variant->id)];

            $amount   = $price * $line['qty'];
            $subtotal += $amount;

            $lines[] = compact('product', 'variant') + [
                'qty'    => $line['qty'],
                'price'  => $price,
                'amount' => $amount,
            ];
        }
        $igv   = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        // 2) Validaciones de entrada
        $data = $request->validate([
            'payment_method'      => 'required|in:transfer,cod,online',
            'shipping_mode'       => 'required|in:pickup,deposit,to_be_quoted',
            'shipping_address_id' => 'nullable|integer',
            'shipping_deposit'    => 'nullable|numeric|min:0',
            // extras de COD si quieres validar: 'cod_pay_type' => 'nullable|in:cash,yape,plin'
        ]);

        $shippingMode      = $data['shipping_mode'];
        $shippingAddressId = null;
        $shippingZoneId    = null;
        $shippingRateId    = null;
        $shippingEstimated = null;
        $shippingDeposit   = 0.0;
        $shippingAmount    = 0.0;   // lo que se cobra HOY
        $settlementStatus  = 'unsettled';

        // 3) Lógica por modo logístico
        if ($shippingMode === 'pickup') {
            // Recojo en tienda: no hay dirección ni envío hoy
            $settlementStatus = 'settled';
            $shippingAmount   = 0.0;
        } elseif ($shippingMode === 'to_be_quoted') {
            // Por cotizar: no cobras envío hoy
            $shippingAmount   = 0.0;
            $settlementStatus = 'unsettled';
        } else { // deposit
            // Debe venir una dirección del usuario
            $address = Address::where('user_id', $user->id)
                ->findOrFail($data['shipping_address_id']);

            // Buscar zona por distrito (si la tienes configurada)
            $zone = ShippingZone::findByDistrict($address->district);
            $rate = $zone?->rates()->orderBy('price')->first();

            $shippingAddressId = $address->id;
            $shippingZoneId    = $zone?->id;
            $shippingRateId    = $rate?->id;
            $shippingEstimated = $rate?->price ?? null;

            // Depósito editable desde el GET
            $shippingDeposit = (float)($data['shipping_deposit'] ?? 0);
            $shippingAmount  = $shippingDeposit; // lo cobrado hoy
            $settlementStatus = 'unsettled';
        }

        $grandTotal = round($total + $shippingAmount, 2);

        // 4) Crear orden
        $order = Order::create([
            'user_id'                   => $user->id,
            'subtotal'                  => $subtotal,
            'tax'                       => $igv,
            'total'                     => $total,

            'shipping_mode'             => $shippingMode,
            'shipping_address_id'       => $shippingAddressId,
            'shipping_zone_id'          => $shippingZoneId,
            'shipping_rate_id'          => $shippingRateId,
            'shipping_estimated'        => $shippingEstimated,
            'shipping_deposit'          => $shippingDeposit,
            'shipping_actual'           => null,
            'shipping_amount'           => $shippingAmount,
            'grand_total'               => $grandTotal,

            'shipping_settlement_status' => $settlementStatus,

            'status'                    => 'new',
            'payment_method'            => $data['payment_method'],
            'payment_status'            => $data['payment_method'] === 'online' ? 'authorized' : 'unpaid',
        ]);

        // 5) Guardar ítems (ajusta a tu OrderItem)
        foreach ($lines as $l) {
            OrderItem::create([
                'order_id'      => $order->id,
                'product_id'    => $l['product']->id,
                'variant_id'    => $l['variant']->id,
                'name'          => $l['product']->name,
                'sku'           => $l['variant']->sku,
                'unit_price' => $l['price'],
                'qty'           => $l['qty'],
                'amount'        => $l['amount'],
            ]);
        }

        // 6) Registrar pago "inicial" (opcional, según tu flujo)
        //    Si manejas un solo pago por grand_total al confirmar:
        OrderPayment::create([
            'order_id'   => $order->id,
            'method'     => $data['payment_method'], // transfer/cod/online
            'kind'       => 'charge',                // cargo normal
            'amount'     => $grandTotal,
            'status'     => $data['payment_method'] === 'online' ? 'authorized' : 'pending',
            'notes'      => $data['payment_method'] === 'cod' ? ($request->input('cod_pay_type') ?? null) : null,
        ]);

        // 7) Vaciar carrito
        session()->forget('cart');

        // 8) Redirigir a gracias (o detalle de pedido)
        return redirect()->route('checkout.thanks', $order)->with('success', 'Pedido creado correctamente.');
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
