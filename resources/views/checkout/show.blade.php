<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Checkout</h2>
    </x-slot>

    <div class="p-6 space-y-6">

        @php
        $isWholesale = auth()->user()?->isWholesale() ?? false;
        $minFirst = \App\Models\Setting::getValue('wholesale_first_order_min', 160.00);
        @endphp

        @if($isWholesale && $grandTotal < $minFirst)
            <div class="bg-yellow-100 text-yellow-800 p-2 rounded mb-3 text-sm">
            Nota: Tu primera compra mayorista debe ser al menos S/ {{ number_format($minFirst,2) }}.
    </div>
    @endif

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
                        <select id="addressSelect" name="shipping_address_id" class="border p-2 w-full" onchange="this.form.submit()">
                            @forelse($addresses as $addr)
                            <option value="{{ $addr->id }}" @selected($addr->id == $addressId)>
                                {{ $addr->district }} — {{ $addr->line1 }} ({{ $addr->contact_name }})
                            </option>
                            @empty
                            <option value="">(No tienes direcciones)</option>
                            @endforelse
                        </select>

                        {{-- BOTÓN NUEVA DIRECCIÓN (abre modal) --}}
                        <button type="button"
                            class="px-3 py-2 border rounded mt-3 hover:bg-gray-50"
                            onclick="openAddressModal()">
                            + Nueva dirección
                        </button>
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
                <input type="hidden" name="shipping_deposit"
                    value="{{ old('shipping_deposit', $shippingAmount ?: $depositDefault) }}">
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

    {{-- Modal crear dirección --}}
    <div id="addressModal"
        class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
        <div class="bg-white w-full max-w-lg rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Nueva dirección</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" onclick="closeAddressModal()">✕</button>
            </div>

            <form id="addressForm" class="space-y-3">
                @csrf
                <div>
                    <label class="text-sm text-gray-600">Contacto</label>
                    <input name="contact_name" class="border p-2 w-full" required>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Teléfono</label>
                    <input name="phone" class="border p-2 w-full" required>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Distrito</label>
                    <input name="district" class="border p-2 w-full" placeholder="Ej: La Victoria" required>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Dirección (línea 1)</label>
                    <input name="line1" class="border p-2 w-full" placeholder="Calle, número, block…" required>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Referencia (opcional)</label>
                    <input name="reference" class="border p-2 w-full">
                </div>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_default" value="1">
                    <span>Marcar como predeterminada</span>
                </label>

                <div id="addrErrors" class="text-red-600 text-sm hidden"></div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 border rounded" onclick="closeAddressModal()">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddressModal() {
            document.getElementById('addressModal').classList.remove('hidden');
            document.getElementById('addressModal').classList.add('flex');
        }

        function closeAddressModal() {
            document.getElementById('addressModal').classList.add('hidden');
            document.getElementById('addressModal').classList.remove('flex');
        }

        // Envío AJAX del modal
        document.getElementById('addressForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const fd = new FormData(form);

            const res = await fetch(`{{ route('addresses.store') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: fd
            });

            const errBox = document.getElementById('addrErrors');
            errBox.classList.add('hidden');
            errBox.innerHTML = '';

            if (res.ok) {
                const data = await res.json(); // { address: {...} }
                const a = data.address;

                // Append option al select y seleccionarla
                const sel = document.getElementById('addressSelect');
                const opt = new Option(`${a.district} — ${a.line1} (${a.contact_name})`, a.id, true, true);
                if (sel) {
                    // Si el select estaba vacío, primero limpia las opciones
                    if (sel.options.length === 1 && sel.options[0].value === '') sel.options.length = 0;
                    sel.add(opt);
                    sel.value = a.id;
                }

                // Cerrar modal
                closeAddressModal();

                // Recalcular envío con el nuevo address (submit del Form A - GET)
                document.getElementById('shipForm').submit();
            } else {
                // Mostrar validaciones
                try {
                    const payload = await res.json();
                    if (payload?.errors) {
                        const msgs = Object.values(payload.errors).flat();
                        errBox.innerHTML = msgs.map(m => `<div>• ${m}</div>`).join('');
                        errBox.classList.remove('hidden');
                    } else {
                        errBox.textContent = 'Ocurrió un error al guardar la dirección.';
                        errBox.classList.remove('hidden');
                    }
                } catch (_) {
                    errBox.textContent = 'Ocurrió un error al guardar la dirección.';
                    errBox.classList.remove('hidden');
                }
            }
        });
    </script>

</x-app-layout>