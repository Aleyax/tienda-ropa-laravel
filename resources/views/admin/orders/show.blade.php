@extends('admin.layout')

@section('title',"Pedido #{$order->id}")

@section('content')
<div class="grid md:grid-cols-3 gap-4">
  {{-- ====== Columna izquierda (2/3) ====== --}}
  <div class="md:col-span-2 space-y-4">
    {{-- Resumen --}}
    <div class="border rounded">
      <div class="p-3 font-semibold border-b">Resumen</div>
      <div class="p-3 grid grid-cols-2 gap-2">
        <div><span class="text-gray-500">Cliente:</span> {{ $order->user?->name ?? 'Invitado' }}</div>
        <div><span class="text-gray-500">Email:</span> {{ $order->user?->email }}</div>
        <div><span class="text-gray-500">Método:</span> {{ $order->payment_method }}</div>
        <div><span class="text-gray-500">Pago:</span> <strong>{{ $order->payment_status }}</strong></div>
        <div><span class="text-gray-500">Estado:</span> <strong>{{ $order->status }}</strong></div>
        <div><span class="text-gray-500">Creado:</span> {{ $order->created_at->format('Y-m-d H:i') }}</div>
      </div>
      @if($order->voucher_url)
      <div class="p-3">
        <a class="text-blue-600 underline" href="{{ $order->voucher_url }}" target="_blank">Ver voucher</a>
      </div>
      @endif
    </div>

    {{-- Ítems --}}
    <div class="border rounded">
      <div class="p-3 font-semibold border-b">Ítems</div>
      <table class="min-w-full bg-white">
        <thead>
          <tr>
            <th class="p-2 border">Producto</th>
            <th class="p-2 border">Variante</th>
            <th class="p-2 border">Precio</th>
            <th class="p-2 border">Cant.</th>
            <th class="p-2 border">Importe</th>
            <th class="p-2 border">Origen</th>
          </tr>
        </thead>
        <tbody>
          @foreach($order->items as $it)
          @php
          $variant = \App\Models\ProductVariant::with(['product','color','size'])->find($it->variant_id);
          @endphp
          <tr>
            <td class="p-2 border">{{ $variant?->product?->name }}</td>
            <td class="p-2 border">{{ $variant?->color?->name }} / {{ $variant?->size?->code }}</td>
            <td class="p-2 border">S/ {{ number_format($it->unit_price,2) }}</td>
            <td class="p-2 border">{{ $it->qty }}</td>
            <td class="p-2 border">S/ {{ number_format($it->amount,2) }}</td>
            <td class="p-2 border text-xs text-gray-500">{{ $it->price_source }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      <div class="p-3 text-right space-y-1">
        <div>Subtotal: <strong>S/ {{ number_format($order->subtotal,2) }}</strong></div>
        <div>IGV (18%): <strong>S/ {{ number_format($order->tax,2) }}</strong></div>
        <div class="text-xl">Total: <strong>S/ {{ number_format($order->total,2) }}</strong></div>
      </div>
    </div>
  </div>

  {{-- ====== Columna derecha (1/3) ====== --}}
  <div class="space-y-4">
    {{-- Cambiar estado de pago --}}
    <div class="border rounded p-3">
      <div class="font-semibold mb-2">Actualizar pago</div>
      <form method="POST" action="{{ route('admin.orders.paystatus',$order) }}" class="flex gap-2">
        @csrf
        <select name="payment_status" class="border p-2">
          @foreach(['unpaid','pending_confirmation','cod_promised','authorized','paid','failed','partially_paid'] as $ps)
          <option value="{{ $ps }}" @selected($ps===$order->payment_status)>{{ $ps }}</option>
          @endforeach
        </select>
        <button class="bg-gray-800 text-white px-3 rounded">Guardar</button>
      </form>
    </div>

    {{-- Cambiar estado del pedido --}}
    <div class="border rounded p-3">
      <div class="font-semibold mb-2">Actualizar pedido</div>
      <form method="POST" action="{{ route('admin.orders.status',$order) }}" class="flex gap-2">
        @csrf
        <select name="status" class="border p-2">
          @foreach(['new','confirmed','preparing','shipped','delivered','cancelled'] as $s)
          <option value="{{ $s }}" @selected($s===$order->status)>{{ $s }}</option>
          @endforeach
        </select>
        <button class="bg-gray-800 text-white px-3 rounded">Guardar</button>
      </form>
    </div>

    {{-- ===== Bloque: Liquidación de envío ===== --}}
    <div class="border rounded p-3">
      <div class="flex items-center justify-between mb-2">
        <div class="font-semibold">Liquidación de envío</div>
        <div class="text-xs text-gray-600">Modo: <strong>{{ strtoupper($order->shipping_mode) }}</strong></div>
      </div>

      @if($order->shipping_mode === 'pickup')
      <div class="text-gray-700">Recojo en tienda — no aplica envío.</div>
      @else
      <div class="text-sm space-y-1 mb-3">
        <div>Depósito cobrado hoy: <strong>S/ {{ number_format($order->shipping_amount, 2) }}</strong></div>
        <div>Estimado de zona:
          <strong>
            @if(!is_null($order->shipping_estimated))
            S/ {{ number_format($order->shipping_estimated, 2) }}
            @else
            —
            @endif
          </strong>
        </div>
        @if($order->shippingAddress)
        <div class="text-xs text-gray-500">
          Destino: {{ $order->shippingAddress->district }} — {{ $order->shippingAddress->line1 }} ({{ $order->shippingAddress->contact_name }})
        </div>
        @endif
        <div>Estado: <strong>{{ $order->shipping_settlement_status }}</strong></div>
      </div>

      {{-- Guardar costo real --}}
      <form method="POST" action="{{ route('admin.orders.shippingActual', $order) }}" class="flex items-end gap-2 mb-2">
        @csrf
        <div class="flex-1">
          <label class="block text-sm text-gray-600">Costo real courier</label>
          <input type="number" step="0.01" min="0" name="shipping_actual"
            value="{{ old('shipping_actual', $order->shipping_actual) }}"
            class="border p-2 w-full" id="shipping_actual">
        </div>
        <button class="bg-gray-800 text-white px-3 py-2 rounded">Guardar</button>
      </form>

      @php
      $__diff = is_null($order->shipping_actual)
      ? null
      : round($order->shipping_amount - $order->shipping_actual, 2); // + => refund, - => charge
      @endphp

      <div class="text-sm mb-2">
        Diferencia (depositado - real):
        <strong id="diff_value">
          @if(!is_null($__diff)) S/ {{ number_format($__diff, 2) }} @else — @endif
        </strong>
        <div class="text-xs text-gray-500" id="diff_hint">
          @if(!is_null($__diff))
          @if($__diff > 0) → Reembolso sugerido
          @elseif($__diff < 0) → Cobro adicional sugerido
            @else Sin diferencia
            @endif
            @else
            Ingresa el costo real para calcular la diferencia.
            @endif
            </div>
        </div>

        <div class="flex gap-2">
          {{-- Registrar reembolso (cuando diff > 0) --}}
          <form method="POST" action="{{ route('admin.orders.settlement.refund', $order) }}">
            @csrf
            <button class="px-3 py-2 bg-emerald-600 text-white rounded"
              @disabled(is_null($order->shipping_actual) || ($__diff ?? 0) <= 0)
                title="Registrar devolución si el real fue menor">
                Registrar reembolso
            </button>
          </form>

          {{-- Registrar cargo adicional (cuando diff < 0) --}}
          <form method="POST" action="{{ route('admin.orders.settlement.charge', $order) }}">
            @csrf
            <button class="px-3 py-2 bg-orange-600 text-white rounded"
              @disabled(is_null($order->shipping_actual) || ($__diff ?? 0) >= 0)
              title="Registrar cobro adicional si el real fue mayor">
              Cobro adicional
            </button>
          </form>
        </div>

        @if($order->shipping_settlement_status === 'settled' && $order->settled_at)
        <div class="mt-2 text-xs text-gray-600">
          Liquidación cerrada el {{ $order->settled_at->format('d/m/Y H:i') }}.
        </div>
        @endif
        @endif
      </div>

      {{-- Flash feedback (si no lo manejas global) --}}
      @if(session('success'))
      <div class="bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
      @endif
      @if(session('error'))
      <div class="bg-red-100 text-red-800 p-2 rounded">{{ session('error') }}</div>
      @endif
    </div>
  </div>

  {{-- JS: cálculo en vivo de diferencia (opcional) --}}
  <script>
    (function() {
      const input = document.getElementById('shipping_actual');
      if (!input) return;

      // ✅ Seguro para el linter y para runtime
      const deposited = parseFloat("{{ $order->shipping_amount !== null ? number_format((float) $order->shipping_amount, 2, '.', '') : 0 }}") || 0;

      const diffValue = document.getElementById('diff_value');
      const diffHint = document.getElementById('diff_hint');

      input.addEventListener('input', () => {
        const v = parseFloat(input.value || '0');
        if (isNaN(v)) return;
        const d = (deposited - v);
        diffValue.textContent = 'S/ ' + d.toFixed(2);
        if (d > 0) diffHint.textContent = '→ Reembolso sugerido';
        else if (d < 0) diffHint.textContent = '→ Cobro adicional sugerido';
        else diffHint.textContent = 'Sin diferencia';
      });
    })();
  </script>

  @endsection