<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Tu Carrito</h2>
    </x-slot>

    <div class="p-6 space-y-4">

        {{-- Alertas --}}
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-800 p-2 rounded">{{ session('error') }}</div>
        @endif

        {{-- Banner modo mayorista --}}
        @if($isWholesale)
            @if(isset($ordersCount) && $ordersCount === 0)
                <div class="bg-blue-50 border border-blue-200 text-blue-800 p-3 rounded text-sm mt-2">
                    Primera compra mayorista: mínimo S/ {{ number_format($minFirst, 2) }}.
                </div>
            @else
                <div class="bg-blue-50 border border-blue-200 text-blue-800 p-3 rounded text-sm mt-2">
                    Regla mayorista: mínimo {{ $minUnitsCart }} unidades totales por compra.
                </div>
            @endif
        @endif

        @if(empty($lines))
            <p>Tu carrito está vacío.</p>
        @else
            <table class="min-w-full bg-white border mb-4">
                <thead>
                    <tr>
                        <th class="p-2 border">Producto</th>
                        <th class="p-2 border">Variante</th>
                        <th class="p-2 border">Precio</th>
                        <th class="p-2 border">Cantidad</th>
                        <th class="p-2 border">Estado</th>
                        <th class="p-2 border">Importe</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $l)
                        @php
                            $variant = \App\Models\ProductVariant::with(['product', 'color', 'size'])->find($l['variant_id']);
                            $stock = (int) ($variant->stock ?? 0);
                            $qty = (int) $l['qty'];
                            $inmediato = min($qty, $stock);
                            $backorder = max(0, $qty - $stock);
                        @endphp
                        <tr>
                            <td class="p-2 border">{{ $variant->product->name }}</td>
                            <td class="p-2 border">{{ $variant->color->name }} / {{ $variant->size->code }}</td>
                            <td class="p-2 border">
                                S/ {{ number_format($l['price'], 2) }}
                                <span class="text-xs text-gray-500">({{ $l['source'] }})</span>
                            </td>
                            <td class="p-2 border">
                                {{-- Form por fila para actualizar una sola línea --}}
                                <form method="POST" action="{{ route('cart.update') }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $l['variant_id'] }}">
                                    <input type="number" name="qty" value="{{ $qty }}" min="0" class="border p-1 w-20">
                                    <button class="bg-gray-800 text-white px-2 py-1 rounded text-sm">Actualizar</button>
                                </form>
                                @if(empty($isWholesale) || !$isWholesale)
                                    <div class="text-xs text-gray-500 mt-1">
                                        Stock disponible: {{ $stock }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-2 border">
                                @if(!empty($isWholesale) && $isWholesale)
                                    @if($backorder > 0)
                                        <span class="text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-800">
                                            Backorder: {{ $backorder }}
                                        </span>
                                        <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800 ml-1">
                                            Inmediato: {{ $inmediato }}
                                        </span>
                                    @else
                                        <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800">
                                            Inmediato
                                        </span>
                                    @endif
                                @else
                                    @if($stock >= $qty)
                                        <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800">
                                            Inmediato
                                        </span>
                                    @else
                                        <span class="text-xs px-2 py-1 rounded bg-red-100 text-red-800">
                                            Sin stock
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td class="p-2 border">S/ {{ number_format($l['amount'], 2) }}</td>
                            <td class="p-2 border">
                                <form method="POST" action="{{ route('cart.remove') }}">
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $l['variant_id'] }}">
                                    <button class="text-red-600">Quitar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="text-right space-y-1">
                <div>Subtotal: <strong>S/ {{ number_format($subtotal, 2) }}</strong></div>
                <div>IGV (18%): <strong>S/ {{ number_format($igv, 2) }}</strong></div>
                <div class="text-xl">Total: <strong>S/ {{ number_format($total, 2) }}</strong></div>
            </div>

            <div class="mt-4 text-right">
                <a href="/checkout" class="bg-blue-600 text-white px-4 py-2 rounded">Ir a pagar</a>
            </div>
        @endif
    </div>
</x-app-layout>