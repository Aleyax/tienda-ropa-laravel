<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Order;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index(PricingService $pricing)
    {
        $minUnitsCart = (int) \App\Models\Setting::getValue('wholesale_min_units_cart', 3);
        $cart = session()->get('cart', []);
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $isWholesale = $user instanceof User ? $user->isWholesale() : false;

        $lines = [];
        $subtotal = 0;
        foreach ($cart as $line) {
            $product = Product::find($line['product_id']);
            $variant = ProductVariant::find($line['variant_id']);
            [$price, $source] = method_exists($pricing, 'priceForWithSource')
                ? $pricing->priceForWithSource($user, $product, optional($variant)->id)
                : [$pricing->priceFor($user, $product, optional($variant)->id), 'auto'];

            $line['price']  = $price;
            $line['source'] = $source;
            $line['amount'] = $price * $line['qty'];
            $subtotal += $line['amount'];
            $lines[] = $line;
        }
        $igv   = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        // (Opcional) Datos para banners
        $minFirst   = (float) \App\Models\Setting::getValue('wholesale_first_order_min', 160.00);
        $minUnits   = (int)   \App\Models\Setting::getValue('wholesale_min_units_per_item', 3);
        $ordersCount = $isWholesale
            ? Order::where('user_id', $user->id)->where('status', '!=', 'cancelled')->count()
            : 0;

        return view('cart.index', compact(
            'lines',
            'subtotal',
            'igv',
            'total',
            'isWholesale',
            'minFirst',
            'ordersCount',
            'minUnitsCart'
        ));
    }



    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'required|integer|exists:product_variants,id',
            'qty'        => 'required|integer|min:1',
        ]);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $isWholesale = $user instanceof User ? $user->isWholesale() : false;

        $product = Product::findOrFail($data['product_id']);
        $variant = ProductVariant::with(['product'])->findOrFail($data['variant_id']);

        if ($variant->product_id !== $product->id) {
            return back()->with('error', 'La variante no corresponde al producto.');
        }

        $available = (int) $variant->stock;
        $requested = (int) $data['qty'];

        $cart = session('cart', []);
        $currentQtyInCart = 0;
        foreach ($cart as $line) {
            if ((int)$line['variant_id'] === (int)$variant->id) {
                $currentQtyInCart += (int)$line['qty'];
            }
        }

        $maxAddable = $isWholesale ? $requested : max(0, $available - $currentQtyInCart);

        if (!$isWholesale && $maxAddable <= 0) {
            return back()->with('error', "No hay más stock disponible para {$variant->sku}.");
        }

        $finalToAdd = min($requested, $maxAddable);
        $adjusted   = !$isWholesale && $finalToAdd !== $requested;

        // Agregar/actualizar línea en carrito
        $found = false;
        foreach ($cart as &$line) {
            if ((int)$line['variant_id'] === (int)$variant->id) {
                $line['qty'] += $finalToAdd;
                $found = true;
                break;
            }
        }
        unset($line);

        if (!$found) {
            $cart[] = [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'qty'        => $finalToAdd,
            ];
        }

        session(['cart' => $cart]);

        return back()->with(
            $adjusted ? 'error' : 'success',
            $adjusted
                ? "Se ajustó la cantidad por stock. Añadido: {$finalToAdd} (SKU {$variant->sku})."
                : 'Producto añadido al carrito.'
        );
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $isWholesale = $user instanceof User ? $user->isWholesale() : false;

        // Caso simple: un solo item
        $isScalar = $request->has('variant_id') && !is_array($request->input('variant_id'));

        if ($isScalar) {
            $data = $request->validate([
                'variant_id' => 'required|integer|exists:product_variants,id',
                'qty'        => 'required|integer|min:0',
            ]);

            $variant = ProductVariant::findOrFail((int)$data['variant_id']);
            $requested = (int)$data['qty'];
            $allowed = $isWholesale ? $requested : max(0, min($requested, (int)$variant->stock));

            $cart = session('cart', []);
            $changed = false;

            if ($allowed === 0 && $requested === 0) {
                foreach ($cart as $i => $line) {
                    if ((int)$line['variant_id'] === (int)$variant->id) {
                        unset($cart[$i]);
                        $changed = true;
                        break;
                    }
                }
                session(['cart' => array_values($cart)]);
                return back()->with('success', 'Ítem eliminado del carrito.');
            }

            foreach ($cart as $i => $line) {
                if ((int)$line['variant_id'] === (int)$variant->id) {
                    $cart[$i]['qty'] = $allowed;
                    $changed = true;
                    break;
                }
            }
            session(['cart' => array_values($cart)]);

            if (!$isWholesale && $allowed !== $requested) {
                return back()->with('error', "Cantidad ajustada por stock para {$variant->sku}. Nueva cantidad: {$allowed}.");
            }
            return back()->with('success', 'Carrito actualizado.');
        }

        // Caso arrays (form global)
        $data = $request->validate([
            'variant_id'   => 'required|array',
            'variant_id.*' => 'integer|exists:product_variants,id',
            'qty'          => 'required|array',
            'qty.*'        => 'integer|min:0',
        ]);

        $cart = session('cart', []);
        $byVariant = [];
        foreach ($cart as $i => $line) {
            $byVariant[(int)$line['variant_id']] = $i;
        }

        $adjustedMsgs = [];

        foreach ($data['variant_id'] as $k => $variantId) {
            $variantId = (int)$variantId;
            $requested = (int)$data['qty'][$k];

            if (!array_key_exists($variantId, $byVariant)) continue;

            $variant = ProductVariant::findOrFail($variantId);
            $allowed = $isWholesale ? $requested : max(0, min($requested, (int)$variant->stock));
            $idx     = $byVariant[$variantId];

            if ($allowed <= 0) {
                unset($cart[$idx]);
                if ($requested > 0) {
                    $adjustedMsgs[] = "Se eliminó {$variant->sku} por falta de stock.";
                }
                continue;
            }

            if (!$isWholesale && $allowed !== $requested) {
                $adjustedMsgs[] = "SKU {$variant->sku}: solicitado {$requested}, ajustado a {$allowed}.";
            }
            $cart[$idx]['qty'] = $allowed;
        }

        session(['cart' => array_values($cart)]);

        if (!empty($adjustedMsgs)) {
            return back()->with('error', implode(' ', $adjustedMsgs));
        }
        return back()->with('success', 'Carrito actualizado.');
    }

    public function remove(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
        ]);

        $cart = session()->get('cart', []);
        $variantId = (int)$data['variant_id'];

        foreach ($cart as $i => $line) {
            if ((int)$line['variant_id'] === $variantId) {
                unset($cart[$i]);
                break;
            }
        }

        session(['cart' => array_values($cart)]);
        return back()->with('success', 'Producto eliminado del carrito.');
    }
}
