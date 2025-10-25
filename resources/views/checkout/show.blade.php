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

        {{-- ========================= --}}
        {{-- Form A (GET) - Envío/Recojo --}}
        {{-- ========================= --}}
        <form method="GET" action="{{ route('checkout.show') }}" id="shippingForm" class="bg-white border rounded p-4 space-y-3">
            <div class="font-semibold">Entrega</div>

            {{-- Radios modo --}}
            <label class="flex items-center gap-2">
                <input type="radio" name="shipping_mode" value="pickup"
                       @checked($shippingMode==='pickup')
                       onchange="document.getElementById('shippingForm').submit()">
                Recojo en tienda (S/ 0.00)
            </label>

            <label class="flex items-center gap-2">
                <input type="radio" name="shipping_mode" value="deposit"
                       @checked($shippingMode==='deposit')
                       onchange="document.getElementById('shippingForm').submit()">
                Envío a domicilio con depósito
            </label>

            <label class="flex items-center gap-2">
                <input type="radio" name="shipping_mode" value="to_be_quoted"
                       @checked($shippingMode==='to_be_quoted')
                       onchange="document.getElementById('shippingForm').submit()">
                Envío por cotizar (pagarás el envío luego)
            </label>

            {{-- Si es depósito, dirección + depósito --}}
            @if($shippingMode === 'deposit')
                <div class="grid md:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Dirección de entrega</label>
                        <select name="shipping_address_id" class="border p-2 w-full"
                                onchange="document.getElementById('shippingForm').submit()">
                            @forelse($addresses as $addr)
                                <option value="{{ $addr->id }}" @selected($addr->id == $addressId)>
                                    {{ $addr->district }} — {{ $addr->line1 }} ({{ $addr->contact_name }})
                                </option>
                            @empty
                                <option value="">(No tienes direcciones)</option>
                            @endforelse
                        </select>

                        {{-- Botón para crear dirección en modal --}}
                        <div class="mt-2">
                            <button type="button"
                                    class="px-3 py-1.5 border rounded text-sm"
                                    onclick="openAddressModal()">
                                + Nueva dirección
                            </button>
                        </div>

                        @if($addresses->isEmpty())
                            <div class="text-sm text-gray-500 mt-1">
                                También puedes ir a <a class="text-blue-600 underline" href="{{ route('addresses.index') }}">Mis direcciones</a>.
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Depósito de envío (editable)</label>
                        <input type="number" step="0.01" min="0" name="shipping_deposit" class="border p-2 w-full"
                               value="{{ old('shipping_deposit', $shippingAmount ?: $depositDefault) }}">
                        <div class="text-xs text-gray-500 mt-1">
                            Estimado zona: S/ {{ number_format($shippingEstimated ?? 0,2) }}
                            @if($shippingZone)<span>({{ $shippingZone->name }})</span>@endif
                        </div>
                    </div>
                </div>
            @endif
        </form>

        {{-- ============================== --}}
        {{-- Form B (POST) - Confirmar pago --}}
        {{-- ============================== --}}
        <form method="POST" action="{{ route('checkout.place') }}" class="space-y-4" enctype="multipart/form-data">
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

            {{-- Resumen de totales (con envío calculado) --}}
            <div class="bg-white border rounded p-4 space-y-1">
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

            {{-- Copiar selección del Form A (GET) para enviarla al place() --}}
            <input type="hidden" name="shipping_mode" value="{{ $shippingMode }}">
            @if($shippingMode === 'deposit')
                <input type="hidden" name="shipping_address_id" value="{{ $addressId }}">
                <input type="hidden" name="shipping_deposit" value="{{ old('shipping_deposit', $shippingAmount ?: $depositDefault) }}">
            @endif

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

    {{-- ======================== --}}
    {{-- Modal Nueva Dirección   --}}
    {{-- ======================== --}}
    <div id="addressModal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
      <div class="bg-white w-full max-w-lg rounded shadow-lg">
        <div class="px-4 py-3 border-b flex items-center justify-between">
          <h3 class="font-semibold">Nueva dirección</h3>
          <button class="text-gray-500" onclick="closeAddressModal()">✕</button>
        </div>

        <form id="newAddressForm" class="p-4 space-y-3" onsubmit="return submitAddressAjax(event)">
          @csrf
          <div class="grid md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm text-gray-600">Contacto</label>
              <input name="contact_name" class="border p-2 w-full" required>
              <div data-error="contact_name" class="text-red-600 text-xs mt-1"></div>
            </div>
            <div>
              <label class="block text-sm text-gray-600">Teléfono</label>
              <input name="phone" class="border p-2 w-full" required>
              <div data-error="phone" class="text-red-600 text-xs mt-1"></div>
            </div>
            <div>
              <label class="block text-sm text-gray-600">Distrito</label>
              {{-- Reemplaza por un <select> con tu lista controlada si ya la tienes --}}
              <input name="district" class="border p-2 w-full" placeholder="Ej. Miraflores" required>
              <div data-error="district" class="text-red-600 text-xs mt-1"></div>
            </div>
            <div>
              <label class="block text-sm text-gray-600">Dirección</label>
              <input name="line1" class="border p-2 w-full" required>
              <div data-error="line1" class="text-red-600 text-xs mt-1"></div>
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm text-gray-600">Referencia (opcional)</label>
              <input name="reference" class="border p-2 w-full">
              <div data-error="reference" class="text-red-600 text-xs mt-1"></div>
            </div>
          </div>

          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_default" value="1"> Usar como predeterminada
          </label>

          <div id="addr_general_error" class="text-red-700 text-sm"></div>

          <div class="flex justify-end gap-2 pt-2 border-t">
            <button type="button" class="px-3 py-2 border rounded" onclick="closeAddressModal()">Cancelar</button>
            <button id="addr_save_btn" class="px-3 py-2 bg-blue-600 text-white rounded">Guardar y usar</button>
          </div>
        </form>
      </div>
    </div>

    {{-- ======================== --}}
    {{-- Scripts                 --}}
    {{-- ======================== --}}
    <script>
      // --- Helpers modal ---
      function openAddressModal() {
        const m = document.getElementById('addressModal');
        m.classList.remove('hidden'); m.classList.add('flex');
        clearAddressErrors();
      }
      function closeAddressModal() {
        const m = document.getElementById('addressModal');
        m.classList.add('hidden'); m.classList.remove('flex');
        clearAddressErrors();
        document.getElementById('newAddressForm').reset();
      }
      function clearAddressErrors() {
        document.getElementById('addr_general_error').textContent = '';
        document.querySelectorAll('#newAddressForm [data-error]').forEach(el => el.textContent = '');
      }

      // --- AJAX submit ---
      async function submitAddressAjax(e) {
        e.preventDefault();
        clearAddressErrors();

        const form = document.getElementById('newAddressForm');
        const btn  = document.getElementById('addr_save_btn');
        btn.disabled = true; btn.textContent = 'Guardando...';

        const fd = new FormData(form);
        try {
          const res = await fetch(`{{ route('addresses.store') }}`, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: fd
          });

          if (!res.ok) {
            if (res.status === 422) {
              const data = await res.json();
              const errs = data.errors || {};
              Object.keys(errs).forEach(name => {
                const holder = form.querySelector(`[data-error="${name}"]`);
                if (holder) holder.textContent = errs[name][0] || 'Campo inválido';
              });
              if (data.message) {
                document.getElementById('addr_general_error').textContent = data.message;
              }
            } else {
              document.getElementById('addr_general_error').textContent = 'No se pudo guardar la dirección. Intenta de nuevo.';
            }
            return false;
          }

          const data = await res.json();
          if (data.ok && data.address) {
            // 1) Inyectar nueva opción en el select y seleccionarla
            const sel = document.querySelector('select[name="shipping_address_id"]');
            if (sel) {
              const opt = new Option(data.address.label, data.address.id, true, true);
              sel.add(opt);
              sel.value = data.address.id;
            }

            // 2) Cerrar modal
            closeAddressModal();

            // 3) Recalcular checkout (GET) manteniendo modo 'deposit'
            const shippingForm = document.getElementById('shippingForm');
            if (shippingForm) {
              const hiddenAddr = document.createElement('input');
              hiddenAddr.type  = 'hidden';
              hiddenAddr.name  = 'shipping_address_id';
              hiddenAddr.value = data.address.id;
              shippingForm.appendChild(hiddenAddr);
              shippingForm.submit();
            }
          }
        } catch (err) {
          document.getElementById('addr_general_error').textContent = 'Error de red. Intenta nuevamente.';
        } finally {
          btn.disabled = false; btn.textContent = 'Guardar y usar';
        }
        return false;
      }

      // Toggle secciones del método de pago
      function toggleSections() {
        const method = document.getElementById('payment_method').value;
        document.getElementById('cod_section').classList.toggle('hidden', method !== 'cod');
        document.getElementById('transfer_section').classList.toggle('hidden', method !== 'transfer');
      }
      toggleSections();

      // Cerrar modal con ESC y clic fuera
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAddressModal();
      });
      document.getElementById('addressModal')?.addEventListener('click', (e) => {
        if (e.target.id === 'addressModal') closeAddressModal();
      });
    </script>
</x-app-layout>
