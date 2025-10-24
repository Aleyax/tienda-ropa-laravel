<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Demo de Precios por Grupo</h2>
    </x-slot>

    <div class="p-6">
        <p class="mb-2 text-sm text-gray-600">
            Tu grupo: <strong>{{ optional(Auth::user()->group)->name ?? 'sin grupo' }}</strong>
        </p>

        <table class="min-w-full bg-white border">
            <thead>
                <tr>
                    <th class="p-2 border">Producto</th>
                    <th class="p-2 border">Precio Base</th>
                    <th class="p-2 border">Precio Mostrado (según tu grupo)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td class="p-2 border">{{ $r['name'] }}</td>
                    <td class="p-2 border">S/ {{ number_format($r['base'], 2) }}</td>
                    <td class="p-2 border font-semibold">S/ {{ number_format($r['price'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p class="text-xs text-gray-500 mt-2">Cambia el grupo del usuario en la BD para probar (minorista/mayorista/especial).</p>
    </div>
</x-app-layout>
