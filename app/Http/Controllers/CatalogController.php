<?php


namespace App\Http\Controllers;


use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CatalogController extends Controller
{
public function index()
{
$products = Product::with('media')->where('status','active')->get();
return view('catalog.index', compact('products'));
}


public function show($slug, Request $request, PricingService $pricing)
{
$product = Product::with(['variants.color','variants.size','media'])
->where('slug',$slug)
->firstOrFail();


// 1) Si vienen color y talla por querystring, buscamos esa variante
$colorId = $request->integer('color');
$sizeId = $request->integer('size');


$variant = null;
if ($colorId && $sizeId) {
$variant = $product->variants
->firstWhere(fn($v) => $v->color_id === $colorId && $v->size_id === $sizeId);
}


// 2) Fallback: primera variante disponible
if (!$variant) {
$variant = $product->variants->first();
}


// 3) Precio según grupo y variante
[$price, $source] = method_exists($pricing,'priceForWithSource')
? $pricing->priceForWithSource(Auth::user(), $product, optional($variant)->id)
: [$pricing->priceFor(Auth::user(), $product, optional($variant)->id), 'auto'];


return view('catalog.show', compact('product','variant','price','source'));
}
}