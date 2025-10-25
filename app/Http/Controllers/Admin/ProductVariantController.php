<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function store(Request $r, Product $product)
    {
        $data = $r->validate([
            'color_id'   => ['required','exists:colors,id'],
            'size_id'    => ['required','exists:sizes,id'],
            'sku'        => ['required','string','max:64','unique:product_variants,sku'],
            'barcode'    => ['nullable','string','max:64'],
            'stock'      => ['required','integer','min:0'],
            'price_base' => ['nullable','numeric','min:0'],
        ]);

        // Evitar duplicado color+talla en el mismo producto
        $exists = ProductVariant::where('product_id',$product->id)
            ->where('color_id',$data['color_id'])
            ->where('size_id',$data['size_id'])
            ->exists();

        if ($exists) {
            return back()->with('error','Ya existe una variante con ese color y talla para este producto.')->withInput();
        }

        $data['product_id'] = $product->id;
        ProductVariant::create($data);

        return back()->with('success','Variante creada.');
    }

    public function update(Request $r, ProductVariant $variant)
    {
        $data = $r->validate([
            'sku'        => ['required','string','max:64', Rule::unique('product_variants','sku')->ignore($variant->id)],
            'barcode'    => ['nullable','string','max:64'],
            'stock'      => ['required','integer','min:0'],
            'price_base' => ['nullable','numeric','min:0'],
        ]);

        $variant->update($data);
        return back()->with('success','Variante actualizada.');
    }

    public function destroy(ProductVariant $variant)
    {
        $variant->delete();
        return back()->with('success','Variante eliminada.');
    }
}
