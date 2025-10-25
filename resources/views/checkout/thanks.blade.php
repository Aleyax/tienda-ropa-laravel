<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">¡Gracias por tu compra!</h2>
    </x-slot>

    <div class="p-6 space-y-4">
        @if(session('success'))
        <div class="bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
        @endif

        <p>Tu pedido <strong>#{{ $order->id }}</strong> fue creado correctamente.</p>

        <div class="space-x-3">
            <a href="{{ route('catalogo') }}" class="text-blue-600 underline">Seguir comprando</a>
            @auth
            <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 underline">Ver detalle (admin)</a>
            @endauth
        </div>
    </div>
</x-app-layout>