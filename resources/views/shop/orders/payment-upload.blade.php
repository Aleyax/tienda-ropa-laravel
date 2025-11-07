<x-app-layout>
    <h2 class="text-xl font-bold mb-4">Subir comprobante de pago</h2>

    <div class="bg-white shadow p-6 rounded">
        <p class="mb-4 text-gray-700">
            Pedido #{{ $order->id }} — Total: <strong>S/ {{ number_format($order->total, 2) }}</strong>
        </p>

        @if ($order->payments->count())
            <h3 class="font-semibold mb-2">Pagos registrados</h3>
            <table class="w-full text-sm mb-4 border">
                <thead>
                    <tr class="bg-gray-100 border-b">
                        <th class="p-2">Monto</th>
                        <th class="p-2">Método</th>
                        <th class="p-2">Estado</th>
                        <th class="p-2">Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->payments as $p)
                        <tr class="border-b">
                            <td class="p-2">S/ {{ number_format($p->amount, 2) }}</td>
                            <td class="p-2">{{ $p->method }}</td>
                            <td class="p-2">
                                <span class="px-2 py-1 rounded text-xs bg-gray-200">
                                    {{ $p->status }}
                                </span>
                            </td>
                            <td class="p-2">
                                @if($p->evidence_url)
                                    <a href="{{ $p->evidence_url }}" target="_blank" class="text-blue-600 underline">Ver</a>
                                @else
                                    — 
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <form method="POST" action="{{ route('orders.payments.store', $order) }}" enctype="multipart/form-data">
            @csrf

            <label class="block mb-2">Monto</label>
            <input type="number" step="0.01" name="amount" class="border p-2 w-full mb-4" required>

            <label class="block mb-2">Método</label>
            <select name="method" class="border p-2 w-full mb-4" required>
                <option value="Yape">Yape</option>
                <option value="Plin">Plin</option>
                <option value="Transferencia Bancaria">Transferencia Bancaria</option>
            </select>

            <label class="block mb-2">Referencia (opcional)</label>
            <input type="text" name="provider_ref" class="border p-2 w-full mb-4">

            <label class="block mb-2">Comprobante (imagen o PDF)</label>
            <input type="file" name="evidence" class="border p-2 w-full mb-4" required>

            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                Enviar comprobante
            </button>
        </form>
    </div>
</x-app-layout>
