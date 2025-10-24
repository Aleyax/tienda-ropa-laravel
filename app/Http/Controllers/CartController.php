<?php


namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{

    public function index(PricingService $pricing)
    {
        $cart = session()->get('cart', []); // array de líneas


        // Recalcular precios por si cambió la lista/usuario
        $lines = [];
        $subtotal = 0;
        foreach ($cart as $line) {
            $product = Product::find($line['product_id']);
            $variant = ProductVariant::find($line['variant_id']);
            [$price, $source] = method_exists($pricing, 'priceForWithSource')
                ? $pricing->priceForWithSource(Auth::user(), $product, optional($variant)->id)
                : [$pricing->priceFor(Auth::user(), $product, optional($variant)->id), 'auto'];


            $line['price'] = $price; // precio unitario vigente
            $line['source'] = $source; // origen
            $line['amount'] = $price * $line['qty'];
            $subtotal += $line['amount'];
            $lines[] = $line;
        }


        $igv = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);


        return view('cart.index', compact('lines', 'subtotal', 'igv', 'total'));
    }


    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'required|integer|exists:product_variants,id',
            'qty' => 'required|integer|min:1',
        ]);


        $cart = session()->get('cart', []);


        // Unificamos línea por variant_id
        $key = 'v' . $data['variant_id'];
        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $data['qty'];
        } else {
            $cart[$key] = [
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'],
                'qty' => $data['qty'],
            ];
        }


        session()->put('cart', $cart);
        return redirect()->route('cart.index')->with('success', 'Producto añadido al carrito');
    }
    public function update(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'qty' => 'required|integer|min:1',
        ]);


        $cart = session()->get('cart', []);
        $key = 'v' . $data['variant_id'];
        if (isset($cart[$key])) {
            $cart[$key]['qty'] = $data['qty'];
            session()->put('cart', $cart);
        }
        return back();
    }
    public function remove(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
        ]);


        $cart = session()->get('cart', []);
        $key = 'v' . $data['variant_id'];
        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put('cart', $cart);
        }
        return back();
    }
}
