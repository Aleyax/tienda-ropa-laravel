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
use App\Models\ShippingZone;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class CheckoutController extends Controller
{
    public function show(Request $request, PricingService $pricing)
    {
        $cart = session('cart', []);
        if (empty($cart)) return redirect()->route('cart.index');

        $lines = [];
        $subtotal = 0;
        foreach ($cart as $line) {
            $product = Product::find($line['product_id']);
            $variant = ProductVariant::find($line['variant_id']);

            [$price, $source] = method_exists($pricing, 'priceForWithSource')
                ? $pricing->priceForWithSource(Auth::user(), $product, $variant?->id)
                : [$pricing->priceFor(Auth::user(), $product, $variant?->id), 'auto'];

            $qty     = (int)($line['qty'] ?? 1);
            $amount  = $price * $qty;
            $subtotal += $amount;

            $lines[] = compact('product', 'variant') + [
                'qty'    => $qty,
                'price'  => $price,
                'amount' => $amount,
                'source' => $source,
            ];
        }
        $igv   = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        /** @var User|null $user */
        $user      = Auth::user();
        $addresses = $user ? $user->addresses()->orderByDesc('is_default')->get() : collect();

        $shippingMode = $request->input('shipping_mode', 'pickup'); // pickup | deposit | to_be_quoted
        $addressId    = (int) $request->input('shipping_address_id');

        if ($shippingMode === 'deposit' && !$addressId && $addresses->isNotEmpty()) {
            $addressId = $addresses->first()->id;
        }

        $shippingAmount     = 0.0;
        $shippingEstimated  = null;
        $shippingZone       = null;
        $shippingRate       = null;
        $depositDefault     = 50.00;

        if ($shippingMode === 'deposit') {
            $address = $addresses->firstWhere('id', $addressId) ?? $addresses->first();

            if ($address) {
                $shippingZone = ShippingZone::findByDistrict($address->district);
                $shippingRate = $shippingZone?->rates()->orderBy('price')->first();
                $shippingEstimated = $shippingRate?->price ?? 0.0;
            }

            $shippingAmount = (float) $request->input('shipping_deposit', $depositDefault);
        } elseif ($shippingMode === 'to_be_quoted') {
            $shippingAmount    = 0.0;
            $shippingEstimated = null;
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
        try {
            return DB::transaction(function () use ($request, $pricing) {

                /** @var User $user */
                $user = Auth::user();

                // 1) Recalcular carrito
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

                    $qty     = (int)($line['qty'] ?? 1);
                    $amount  = $price * $qty;
                    $subtotal += $amount;

                    $lines[] = compact('product', 'variant') + [
                        'qty'    => $qty,
                        'price'  => $price,
                        'amount' => $amount,
                    ];
                }
                $igv   = round($subtotal * 0.18, 2);
                $total = round($subtotal + $igv, 2);

                // 2) Validaciones de entrada
                $data = $request->validate([
                    'payment_method'       => 'required|in:transfer,cod,online',
                    'shipping_mode'        => 'required|in:pickup,deposit,to_be_quoted',
                    'shipping_address_id'  => 'required_if:shipping_mode,deposit|integer|exists:addresses,id',
                    'shipping_deposit'     => 'required_if:shipping_mode,deposit|numeric|min:0',
                ]);

                // 3) Envío
                $shippingMode      = $data['shipping_mode'];
                $shippingAddressId = null;
                $shippingZoneId    = null;
                $shippingRateId    = null;
                $shippingEstimated = null;
                $shippingDeposit   = 0.0;
                $shippingAmount    = 0.0;
                $settlementStatus  = 'unsettled';

                if ($shippingMode === 'pickup') {
                    $settlementStatus = 'settled';
                    $shippingAmount   = 0.0;
                } elseif ($shippingMode === 'to_be_quoted') {
                    $shippingAmount   = 0.0;
                    $settlementStatus = 'unsettled';
                } else { // deposit
                    $address = Address::where('user_id', $user->id)->findOrFail($data['shipping_address_id']);
                    $zone = ShippingZone::findByDistrict($address->district);
                    $rate = $zone?->rates()->orderBy('price')->first();

                    $shippingAddressId = $address->id;
                    $shippingZoneId    = $zone?->id;
                    $shippingRateId    = $rate?->id;
                    $shippingEstimated = $rate?->price ?? null;

                    $shippingDeposit = (float)($data['shipping_deposit'] ?? 0);
                    $shippingAmount  = $shippingDeposit;
                }

                $grandTotal = round($total + $shippingAmount, 2);
                // ---- Reglas de mayorista: mínimos de compra ----

                $isWholesale = $user?->isWholesale() ?? false;

                if ($isWholesale) {
                    // ¿Es primer pedido no cancelado del usuario?
                    $ordersCount = Order::where('user_id', $user->id)
                        ->where('status', '!=', 'cancelled')
                        ->count();

                    // Mínimo para primera compra (S/160 por defecto)
                    $minFirst = (float) Setting::getValue('wholesale_first_order_min', 160.00);
                    if ($ordersCount === 0 && $grandTotal < $minFirst) {
                        return redirect()->route('cart.index')
                            ->with('error', 'Tu primera compra mayorista debe ser al menos S/ ' . number_format($minFirst, 2) . '.');
                    }

                    // (Opcional) Mínimo para cada compra mayorista
                    $minEvery = (float) Setting::getValue('wholesale_every_order_min', 0);
                    if ($minEvery > 0 && $grandTotal < $minEvery) {
                        return redirect()->route('cart.index')
                            ->with('error', 'Cada compra mayorista debe ser al menos S/ ' . number_format($minEvery, 2) . '.');
                    }
                }
                // ---- fin reglas mayorista ----
                // 3.5) Regla Mayorista: mínimo de primera compra
                $isWholesale = method_exists($user, 'isWholesale') ? ($user->isWholesale() ?? false) : false;

                if ($isWholesale) {
                    $ordersCount = Order::where('user_id', $user->id)
                        ->where('status', '!=', 'cancelled')
                        ->count();

                    if ($ordersCount === 0) {
                        $min = (float) Setting::getValue('wholesale_first_order_min', 160.00);
                        if ($grandTotal < $min) {
                            throw new \RuntimeException(
                                'Tu primera compra mayorista debe ser al menos S/ ' . number_format($min, 2) . '.'
                            );
                        }
                    }
                }
                $orderType = ($user?->isWholesale() ?? false) ? 'wholesale' : 'retail';
                // 4) Crear orden (igual para ambos; la diferencia está en ítems y stock)
                $order = Order::create([
                    'user_id'                    => $user->id,
                    'subtotal'                   => $subtotal,
                    'tax'                        => $igv,
                    'total'                      => $total,

                    'shipping_mode'              => $shippingMode,
                    'shipping_address_id'        => $shippingAddressId,
                    'shipping_zone_id'           => $shippingZoneId,
                    'shipping_rate_id'           => $shippingRateId,
                    'shipping_estimated'         => $shippingEstimated,
                    'shipping_deposit'           => $shippingDeposit,
                    'shipping_actual'            => null,
                    'shipping_amount'            => $shippingAmount,
                    'grand_total'                => $grandTotal,

                    'shipping_settlement_status' => $settlementStatus,

                    'status'                     => 'new',
                    'payment_method'             => $data['payment_method'],
                    'payment_status'             => $data['payment_method'] === 'online' ? 'authorized' : 'unpaid',
                    'order_type'     => $orderType,
                    'is_priority'    => $orderType === 'retail',   // retail prioritario
                    'priority_level' => $orderType === 'retail' ? 10 : 0,
                ]);

                /*
                 * 5) RAMA DE CUMPLIMIENTO
                 * - Retail (minorista): stock duro (lock + decrement).
                 * - Wholesale (mayorista): NO descuenta stock; registra backorder_qty.
                 */
                if ($isWholesale) {
                    // Mayorista: NO tocar stock; solo crear ítems con backorder_qty
                    foreach ($lines as $l) {
                        $variant = $l['variant']; // NO lock, NO decrement
                        OrderItem::create([
                            'order_id'       => $order->id,
                            'product_id'     => $l['product']->id,
                            'variant_id'     => $variant->id,
                            'name'           => $l['product']->name,
                            'sku'            => $variant->sku,
                            'unit_price'     => $l['price'],
                            'qty'            => $l['qty'],           // lo pedido
                            'backorder_qty'  => $l['qty'],           // todo queda pendiente
                            'amount'         => $l['amount'],
                        ]);
                    }
                } else {
                    // Minorista: stock duro con lock
                    foreach ($lines as $l) {
                        $locked = ProductVariant::where('id', $l['variant']->id)->lockForUpdate()->first();
                        if ($locked->stock < $l['qty']) {
                            throw new \RuntimeException("Stock insuficiente para {$locked->sku}. Disponible: {$locked->stock}, requerido: {$l['qty']}");
                        }
                        $locked->decrement('stock', $l['qty']);

                        OrderItem::create([
                            'order_id'       => $order->id,
                            'product_id'     => $l['product']->id,
                            'variant_id'     => $locked->id,
                            'name'           => $l['product']->name,
                            'sku'            => $locked->sku,
                            'unit_price'     => $l['price'],
                            'qty'            => $l['qty'],
                            'backorder_qty'  => 0,                   // retail no deja pendientes
                            'amount'         => $l['amount'],
                        ]);
                    }
                }

                // 6) Registrar pago inicial
                OrderPayment::create([
                    'order_id' => $order->id,
                    'method'   => $data['payment_method'],
                    'kind'     => 'charge',
                    'amount'   => $grandTotal,
                    'status'   => $data['payment_method'] === 'online' ? 'authorized' : 'pending',
                    'notes'    => $data['payment_method'] === 'cod' ? ($request->input('cod_pay_type') ?? null) : null,
                ]);

                // 7) Limpiar carrito y terminar
                session()->forget('cart');
                return redirect()->route('checkout.thanks', $order)->with('success', 'Pedido creado correctamente.');
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('cart.index')->with('error', 'Ocurrió un error al confirmar. Intenta nuevamente.');
        }
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
