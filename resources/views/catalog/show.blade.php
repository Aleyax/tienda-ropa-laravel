<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $product->name }}</h2>
    </x-slot>
    <div class="p-6 grid md:grid-cols-2 gap-6">
        <div>
            <img src="{{ optional($product->media->firstWhere('color_id', optional($variant)->color_id))?->url ?? $product->media->first()->url ?? 'https://via.placeholder.com/500x500' }}" class="w-full" />
        </div>
        <div>
            <div class="mb-2 text-sm text-gray-500">Origen precio: <em>{{ $source }}</em></div>
            <div class="mb-4 text-2xl">S/ {{ number_format($price,2) }}</div>


            <form method="GET" action="">
                <div class="mb-4">
                    <label class="block text-sm mb-1">Color</label>
                    <select name="color" class="border p-2 w-full" onchange="this.form.submit()">
                        @php $currentColorId = optional($variant)->color_id; @endphp
                        @foreach($product->variants->groupBy('color.id') as $cId => $variantsByColor)
                        <option value="{{ $cId }}" @selected($currentColorId==$cId)>
                            {{ $variantsByColor->first()->color->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm mb-1">Talla</label>
                    <select name="size" class="border p-2 w-full" onchange="this.form.submit()">
                        @php $currentSizeId = optional($variant)->size_id; @endphp
                        @foreach($product->variants->groupBy('size.id') as $sId => $variantsBySize)
                        <option value="{{ $sId }}" @selected($currentSizeId==$sId)>
                            {{ $variantsBySize->first()->size->code }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </form>


            <div class="text-sm text-gray-600 mb-4">Stock disponible: <strong>{{ optional($variant)->stock ?? 0 }}</strong></div>


            @if(optional($variant)->stock > 0)
            <form method="POST" action="{{ route('cart.add') }}" class="flex items-center gap-3">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="variant_id" value="{{ optional($variant)->id }}">
                <input type="number" name="qty" value="1" min="1" max="{{ optional($variant)->stock ?? 1 }}" class="border p-2 w-24">

                <button class="bg-blue-600 text-white px-4 py-2 rounded">Añadir al carrito</button>
                @if(session('success'))
                <div class="mb-2 bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                <div class="mb-2 bg-red-100 text-red-800 p-2 rounded">{{ session('error') }}</div>
                @endif
            </form>
            @else
            <div class="text-red-600">Sin stock</div>
            @endif
        </div>
    </div>
</x-app-layout>