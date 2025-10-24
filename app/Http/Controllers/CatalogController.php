<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class CatalogController extends Controller
{
    public function index()
    {
        $products = Product::with('media')->where('status', 'active')->get();
        return view('catalog.index', compact('products'));
    }


    public function show($slug, PricingService $pricing)
    {
        $product = Product::with(['variants.color', 'variants.size', 'media'])->where('slug', $slug)->firstOrFail();


        // default: primera variante disponible
        $variant = $product->variants->first();
        [$price, $source] = method_exists($pricing, 'priceForWithSource')
            ? $pricing->priceForWithSource(Auth::user(), $product, optional($variant)->id)
            : [$pricing->priceFor(Auth::user(), $product, optional($variant)->id), 'auto'];


        return view('catalog.show', compact('product', 'variant', 'price', 'source'));
    }
}
