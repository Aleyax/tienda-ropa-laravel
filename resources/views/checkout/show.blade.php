<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Checkout</h2>
    </x-slot>

    <div class="p-6 space-y-6">

        @if(session('success'))
        <div class="bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-100 text-red-800 p-2 rounded">{{ session('error') }}</div>
        @endif

        {{-- Resumen --}}
        <div class="border rounded">
            <table class="min-w-full bg-white">
                <thead>
                    <tr>
                        <th class="p-2 border">Producto</th>
                        <th class="p-2 border">Variante</th>
                        <th class="p-2 border">Precio</th>
                        <th class="p-2 border">Cantidad</th>
                        <th class="p-2 border">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $l)
                    <tr>
                        <td class="p-2 border">{{ $l['product']->name }}</td>
                        <td class="p-2 border">{{ $l['variant']->color->name }} / {{ $l['variant']->size->code }}</td>
                        <td class="p-2 border">S/ {{ number_format($l['price'],2) }} <span class="text-xs text-gray-500">({{ $l['source'] }})</span></td>
                        <td class="p-2 border">{{ $l['qty'] }}</td>
                        <td class="p-2 border">S/ {{ number_format($l['amount'],2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4 text-right space-y-1">
                <div>Subtotal: <strong>S/ {{ number_format($subtotal,2) }}</strong></div>
                <div>IGV (18%): <strong>S/ {{ number_format($igv,2) }}</strong></div>
                <div class="text-xl">Total: <strong>S/ {{ number_format($total,2) }}</strong></div>
            </div>
        </div>

        {{-- Selección de método y confirmación --}}
        <form method="POST" action="{{ route('checkout.place') }}" class="space-y-4" enctype="multipart/form-data">
            @csrf
            <div>
                <label class="block font-semibold mb-2">Método de pago</label>
                <select name="payment_method" id="payment_method" class="border p-2" onchange="toggleSections()" required>
                    <option value="transfer">Transferencia/Depósito</option>
                    <option value="cod">Contraentrega (Efectivo/Yape/Plin)</option>
                    <option value="online">Online (Sandbox)</option>
                </select>
            </div>

            {{-- COD extra --}}
            <div id="cod_section" class="hidden space-y-2">
                <label class="block text-sm">Forma de pago en puerta</label>
                <select name="cod_pay_type" class="border p-2">
                    <option value="cash">Efectivo</option>
                    <option value="yape">Yape</option>
                    <option value="plin">Plin</option>
                </select>
                <input type="text" name="cod_change" class="border p-2" placeholder="¿Necesitas vuelto? (ej. S/ 200)">
                <p class="text-xs text-gray-500">* Se reconfirmará por WhatsApp antes de enviar.</p>
            </div>

            {{-- Transferencia: (subir voucher luego del place) --}}
            <div id="transfer_section" class="space-y-2">
                <p class="text-sm text-gray-600">* Después de crear el pedido, podrás subir tu voucher aquí mismo.</p>
            </div>

            <button class="bg-blue-600 text-white px-4 py-2 rounded">Confirmar pedido</button>
        </form>

        {{-- Subir voucher para transferencias (si ya hay pedido en sesión->success) --}}
        @if(session('success'))
        <form method="POST" action="{{ route('checkout.voucher') }}" enctype="multipart/form-data" class="border rounded p-4 space-y-3">
            @csrf
            <label class="block font-semibold">Subir voucher (transferencia)</label>
            <input type="number" name="order_id" class="border p-2 w-48" placeholder="ID de pedido" required>
            <input type="file" name="voucher" class="border p-2" required>
            <button class="bg-gray-800 text-white px-4 py-2 rounded">Enviar voucher</button>
        </form>
        @endif
    </div>

    <script>
        function toggleSections() {
            const method = document.getElementById('payment_method').value;
            document.getElementById('cod_section').classList.toggle('hidden', method !== 'cod');
            document.getElementById('transfer_section').classList.toggle('hidden', method !== 'transfer');
        }
        toggleSections();
    </script>
</x-app-layout>