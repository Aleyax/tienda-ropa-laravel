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

    {{-- En la página pública del pedido --}}
    @if ($order->payment_method === 'transfer' && $order->payments()->count() === 0)
        <div class="mt-4 p-3 bg-yellow-50 border rounded">
            <div class="font-semibold">Sube tu comprobante</div>
            <p class="text-sm">Aún no registras pagos para esta orden. Por favor, sube tu comprobante para agilizar la
                validación.</p>
            <a href="{{ route('shop.orders.payments.create', $order) }}"
                class="inline-block mt-2 px-3 py-1 rounded bg-blue-600 text-white">
                Subir comprobante
            </a>
        </div>
    @endif



    {{-- “Pagos registrados” para el cliente --}}
    <div class="mt-4">
        <h3 class="font-semibold">Pagos registrados</h3>
        @if ($order->payments->isEmpty())
            <p class="text-sm text-gray-600">Aún no se han registrado pagos en esta orden.</p>
        @else
            <ul class="text-sm list-disc pl-5">
                @foreach ($order->payments as $p)
                    <li>
                        {{ $p->method }} — S/ {{ number_format($p->amount, 2) }} — {{ $p->status }}
                        @if ($p->evidence_url)
                            — <a class="text-blue-600 underline" href="{{ $p->evidence_url }}" target="_blank">Ver
                                comprobante</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>


</x-app-layout>
