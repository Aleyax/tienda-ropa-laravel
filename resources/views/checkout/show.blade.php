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
        @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-2 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- ======================= RESUMEN DEL CARRITO ======================= --}}
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
                        <td class="p-2 border">S/ {{ number_format($l['price'],2) }}</td>
                        <td class="p-2 border">{{ $l['qty'] }}</td>
                        <td class="p-2 border">S/ {{ number_format($l['amount'],2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4 text-right space-y-1">
                <div>Subtotal: <strong>S/ {{ number_format($subtotal,2) }}</strong></div>
                <div>IGV (18%): <strong>S/ {{ number_format($igv,2) }}</strong></div>
                <div>Total: <strong>S/ {{ number_format($total,2) }}</strong></div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            {{-- ======================= FORM A (GET) RECALCULAR ENVÍO ======================= --}}
            <div class="bg-white border rounded p-4 space-y-3">
                <div class="font-semibold">Entrega</div>

                <form id="shipForm" method="GET" action="{{ route('checkout.show') }}" class="space-y-3">
                    {{-- Radios modo --}}
                    <label class="flex items-center gap-2">
                        <input type="radio" name="shipping_mode" value="pickup"
                            @checked($shippingMode==='pickup' )
                            onchange="this.form.submit()">
                        Recojo en tienda (S/ 0.00)
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="shipping_mode" value="deposit"
                            @checked($shippingMode==='deposit' )
                            onchange="this.form.submit()">
                        Envío a domicilio con depósito
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="shipping_mode" value="to_be_quoted"
                            @checked($shippingMode==='to_be_quoted' )
                            onchange="this.form.submit()">
                        Envío por cotizar (pagarás el envío luego)
                    </label>

                    {{-- Si es depósito, mostrar selección de dirección y monto depósito --}}
                    @if($shippingMode === 'deposit')
                    <div class="grid md:grid-cols-2 gap-3 mt-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Dirección de entrega</label>
                            <select name="shipping_address_id" class="border p-2 w-full" onchange="this.form.submit()">
                                @forelse($addresses as $addr)
                                <option value="{{ $addr->id }}" @selected($addr->id == $addressId)>
                                    {{ $addr->district }} — {{ $addr->line1 }} ({{ $addr->contact_name }})
                                </option>
                                @empty
                                <option value="">(No tienes direcciones)</option>
                                @endforelse
                            </select>
                            @if($addresses->isEmpty())
                            <div class="text-sm text-gray-500 mt-1">
                                Crea una dirección en
                                <a class="text-blue-600 underline" href="{{ route('addresses.index') }}">Mis direcciones</a>.
                            </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Depósito de envío (editable)</label>
                            <input type="number" step="0.01" min="0" name="shipping_deposit" class="border p-2 w-full"
                                value="{{ old('shipping_deposit', $shippingAmount ?: $depositDefault) }}"
                                onblur="this.form.submit()">
                            <div class="text-xs text-gray-500 mt-1">
                                Estimado zona: S/ {{ number_format($shippingEstimated ?? 0,2) }}
                            </div>
                        </div>
                    </div>
                    @endif
                </form>

                {{-- Resumen parcial de envío (lo que cobrará hoy) --}}
                <div class="bg-gray-50 border rounded p-3 space-y-1">
                    <div class="flex justify-between">
                        <span>Envío (hoy)</span> <span>S/ {{ number_format($shippingAmount,2) }}</span>
                    </div>
                </div>
            </div>

            {{-- ======================= FORM B (POST) CONFIRMAR PEDIDO ======================= --}}
            <div class="bg-white border rounded p-4 space-y-4">
                <form id="placeForm" method="POST" action="{{ route('checkout.place') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    {{-- Método de pago --}}
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

                    {{-- Resumen total final --}}
                    <div class="bg-gray-50 border rounded p-4 space-y-1">
                        <div class="flex justify-between">
                            <span>Subtotal</span> <span>S/ {{ number_format($subtotal,2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>IGV (18%)</span> <span>S/ {{ number_format($igv,2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Envío (hoy)</span> <span>S/ {{ number_format($shippingAmount,2) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-semibold">
                            <span>Total a pagar</span> <span>S/ {{ number_format($grandTotal,2) }}</span>
                        </div>
                    </div>

                    {{-- Copiar selección del Form A para enviarla al place() --}}
                    <input type="hidden" name="shipping_mode" value="{{ $shippingMode }}">
                    @if($shippingMode === 'deposit')
                    <input type="hidden" name="shipping_address_id" value="{{ $addressId }}">
                    <input type="hidden" name="shipping_deposit" value="{{ old('shipping_deposit', $shippingAmount ?: $depositDefault) }}">
                    @endif

                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
                        Confirmar pedido
                    </button>
                </form>
            </div>
        </div>

        {{-- Subir voucher (se muestra si hay success tras place) --}}
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