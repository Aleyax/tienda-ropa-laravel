<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User as AppUser;

class CatalogController extends Controller
{
    public function index()
    {
    /** @var AppUser|null $user */
    $user = Auth::user();
    // Defensive: ensure the returned auth user is an instance of our Eloquent User
    $isWholesale = ($user instanceof AppUser) ? $user->isWholesale() : false;

        // Minorista: solo productos activos, pero se muestran también agotados (con badge).
        // Mayorista: mostrar todo el catálogo, salvo descontinuados (solo info, sin comprar).
        $q = Product::with(['media', 'variants.color', 'variants.size'])
            ->orderBy('name');

        // Si quieres ocultar totalmente los descontinuados del listado (opcional):
        // $q->where('discontinued', false);

        $products = $q->get();

        return view('catalog.index', compact('products', 'isWholesale'));
    }

    public function show($slug, PricingService $pricing, Request $request)
    {
    /** @var AppUser|null $user */
    $user = Auth::user();
    $isWholesale = ($user instanceof AppUser) ? $user->isWholesale() : false;

        $product = Product::with(['variants.color', 'variants.size', 'media'])
            ->where('slug', $slug)->firstOrFail();

        // Selección de variante por GET (color/size) o primera variante disponible
        $variant = $product->variants->first();
        $colorId = (int) $request->input('color');
        $sizeId  = (int) $request->input('size');

        if ($colorId || $sizeId) {
            // Use filter(...)->first() to satisfy static analyzers (avoid passing callable to first())
            $variant = $product->variants->filter(function ($v) use ($colorId, $sizeId) {
                $ok = true;
                if ($colorId) $ok = $ok && ((int)$v->color_id === $colorId);
                if ($sizeId)  $ok = $ok && ((int)$v->size_id  === $sizeId);
                return $ok;
            })->first() ?? $variant;
        }

        // Precio según lista (tu PricingService ya lo maneja)
        [$price, $source] = method_exists($pricing, 'priceForWithSource')
            ? $pricing->priceForWithSource($user, $product, optional($variant)->id)
            : [$pricing->priceFor($user, $product, optional($variant)->id), 'auto'];

        // Reglas de compra:
        // - Producto descontinuado: no se puede comprar (todos)
        // - Minorista: no comprar si stock==0
        // - Mayorista: puede comprar sin stock (backorder) si el producto y la variante están habilitados para mayoristas
        $canBuy = true;

        if ($product->discontinued) {
            $canBuy = false;
        } else {
            if ($isWholesale) {
                if (!$product->available_for_wholesale) $canBuy = false;
                if ($variant && !$variant->available_for_wholesale) $canBuy = false;
            } else { // minorista
                if (!$variant || (int)$variant->stock <= 0) $canBuy = false;
            }
        }

        return view('catalog.show', compact('product', 'variant', 'price', 'source', 'isWholesale', 'canBuy'));
    }
}
