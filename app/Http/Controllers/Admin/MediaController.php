<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function store(Request $r, Product $product)
    {
        $data = $r->validate([
            'color_id'   => ['nullable','exists:colors,id'],
            'is_primary' => ['nullable','boolean'],
            'file'       => ['required','file','mimes:jpg,jpeg,png,webp','max:4096'],
        ]);

        $path = $r->file('file')->store('products','public');
        $url  = asset('storage/'.$path); // simple y efectivo en local

        // Si marcamos "principal", desmarcar las demás de este producto
        if ($r->boolean('is_primary')) {
            Media::where('product_id',$product->id)->update(['is_primary'=>false]);
        }

        Media::create([
            'product_id' => $product->id,
            'color_id'   => $data['color_id'] ?? null,
            'url'        => $url,
            'is_primary' => $r->boolean('is_primary'),
        ]);

        return back()->with('success','Imagen subida.');
    }

    public function destroy(Media $media)
    {
        // (Opcional) borrar archivo físico si quieres
        // $relative = str_replace(asset('storage/'), '', $media->url);
        // \Illuminate\Support\Facades\Storage::disk('public')->delete($relative);

        $media->delete();
        return back()->with('success','Imagen eliminada.');
    }
}
