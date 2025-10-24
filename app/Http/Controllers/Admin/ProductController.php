<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = Product::query()->orderByDesc('created_at');
        if ($s = $r->string('s')->toString()) {
            $q->where(fn($w) => $w->where('name', 'like', "%$s%")->orWhere('slug', 'like', "%$s%"));
        }
        $rows = $q->paginate(15)->withQueryString();
        return view('admin.products.index', compact('rows'));
    }

    public function create()
    {
        $product = new Product();
        $mode = 'create';
        return view('admin.products.form', compact('product', 'mode'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'alpha_dash', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', Rule::in(['active', 'draft', 'archived'])],
            'price_base'  => ['required', 'numeric', 'min:0'],
        ]);
        $p = Product::create($data);
        return redirect()->route('admin.products.edit', $p)->with('success', 'Producto creado.');
    }

    public function edit(Product $product)
    {
        $mode = 'edit';
        return view('admin.products.form', compact('product', 'mode'));
    }

    public function update(Request $r, Product $product)
    {
        $data = $r->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'alpha_dash', 'max:255', Rule::unique('products', 'slug')->ignore($product->id)],
            'description' => ['nullable', 'string'],
            'status'      => ['required', Rule::in(['active', 'draft', 'archived'])],
            'price_base'  => ['required', 'numeric', 'min:0'],
        ]);
        $product->update($data);
        return back()->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Producto eliminado.');
    }
}
