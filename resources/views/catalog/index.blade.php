<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Catálogo</h2>
    </x-slot>

    <div class="p-6 grid md:grid-cols-3 gap-6">
        @foreach($products as $p)
            @php
                $firstVariant = $p->variants->first();
                $hasStockRetail = $p->variants->sum('stock') > 0;
                $agotado = !$hasStockRetail; // para minorista
            @endphp

            <a href="{{ url('/producto/'.$p->slug) }}" class="border rounded p-3 block hover:shadow">
                <img src="{{ optional($p->media->first())->url ?? 'https://via.placeholder.com/500x500' }}" class="w-full mb-3" />
                <div class="font-semibold">{{ $p->name }}</div>

                <div class="mt-1 text-xs flex gap-2">
                    @if($p->discontinued)
                        <span class="px-2 py-1 bg-gray-200 text-gray-700 rounded">Descontinuado</span>
                    @endif
                    @if(!$agotado)
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded">Disponible</span>
                    @else
                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded">Agotado</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
</x-app-layout>
