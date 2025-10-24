<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Http\Request;

class DemoPriceController extends Controller
{
    public function index(PricingService $pricing)
    {
        $products = Product::orderBy('name')->get();

        $data = $products->map(function ($p) use ($pricing) {
            return [
                'name'  => $p->name,
                'base'  => $p->price_base,
                'price' => $pricing->priceFor(Auth::user(), $p, null),
            ];
        });

        return view('demo.prices', ['rows' => $data]);
    }
}
