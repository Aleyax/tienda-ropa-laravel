<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $product->name }}</h2>
    </x-slot>
    <div class="p-6 grid md:grid-cols-2 gap-6">
        <div>
            <img src="{{ optional($product->media->firstWhere('color_id', optional($variant)->color_id))?->url ?? $product->media->first()->url ?? 'https://via.placeholder.com/500x500' }}" class="w-full" />
        </div>
        <div>
            <div class="mb-4">Precio: <strong>S/ {{ number_format($price,2) }}</strong> <span class="text-xs text-gray-500">(origen: {{ $source }})</span></div>


            <form method="GET" action="">
                <div class="mb-4">
                    <label class="block text-sm mb-1">Color</label>
                    <select name="color" class="border p-2 w-full" onchange="this.form.submit()">
                        @foreach($product->variants->groupBy('color.name') as $colorName => $variantsByColor)
                        <option value="{{ $variantsByColor->first()->color_id }}" @selected($variantsByColor->contains('id', optional($variant)->id))>{{ $colorName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm mb-1">Talla</label>
                    <select name="size" class="border p-2 w-full" onchange="this.form.submit()">
                        @foreach($product->variants->groupBy('size.code') as $sizeCode => $variantsBySize)
                        <option value="{{ $variantsBySize->first()->size_id }}" @selected($variantsBySize->contains('id', optional($variant)->id))>{{ $sizeCode }}</option>
                        @endforeach
                    </select>
                </div>
            </form>


            <div class="text-sm text-gray-600">Stock: <strong>{{ optional($variant)->stock ?? 0 }}</strong></div>
        </div>
    </div>
</x-app-layout>