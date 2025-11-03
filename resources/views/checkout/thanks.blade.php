<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">¡Gracias por tu compra!</h2>
    </x-slot>

    <div class="p-6 space-y-4">
        @if (session('success'))
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
    @if ($order->payment_method === 'bank_transfer' && $order->payments->count() === 0)
        <div class="p-3 border rounded bg-yellow-50">
            <div class="font-semibold mb-1">Comprobante de pago pendiente</div>
            <p class="text-sm mb-2">
                Por favor, sube el comprobante de tu transferencia/deposito para agilizar la validación.
            </p>
            <form method="POST" action="{{ route('admin.orders.payments.store', $order) }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="method" value="Transferencia">
                <div class="flex flex-col gap-2">
                    <input type="number" step="0.01" name="amount" placeholder="Monto (S/)" class="border p-2"
                        required>
                    <input type="text" name="provider_ref" placeholder="Referencia (opcional)" class="border p-2">
                    <input type="file" name="evidence" class="border p-2" accept=".jpg,.jpeg,.png,.pdf">
                    <button class="bg-blue-600 text-white px-3 py-1 rounded self-start">Subir comprobante</button>
                </div>
            </form>
        </div>
    @endif

</x-app-layout>
