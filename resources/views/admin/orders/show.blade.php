@extends('admin.layout')

@section('title', "Pedido #{$order->id}")

@section('content')

@php
    $meId   = (int)($meId ?? auth()->id() ?? 0);
    $basket = $basket ?? \App\Models\PickBasket::where('order_id', $order->id)->latest()->first();

    $activeUsers = $activeUsers
        ?? \App\Models\User::query()
            ->when(\Schema::hasColumn('users','is_active'), fn($q)=>$q->where('is_active',1))
            ->where('id','!=',$meId)
            ->orderBy('name')
            ->get(['id','name','email']);

    $hasPendingTransfer = $basket
        ? $basket->transfers()->where('status','pending')->exists()
        : false;

    // <<< CAMBIO CLAVE: estados editables
    $editableStatuses = ['open', 'in_progress'];

    // Solo-lectura si: no hay canasta, o estado no editable, o no soy responsable, o hay transferencia pendiente
    $readOnly = !$basket
        || !in_array($basket->status, $editableStatuses, true)
        || (int)$basket->responsible_user_id !== $meId
        || $hasPendingTransfer;

    // Puedo transferir si: hay canasta, estado editable, soy responsable y no hay transferencia pendiente
    $canTransfer = $basket
        && in_array($basket->status, $editableStatuses, true)
        && (int)$basket->responsible_user_id === $meId
        && !$hasPendingTransfer;

    $jsUsers = ($jsUsers ?? $activeUsers->map(fn($u)=>[
        'id'    => $u->id,
        'name'  => $u->name,
        'email' => $u->email,
    ]))->values();
@endphp


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

    {{-- === Picking / Canasta === --}}
    <div class="border rounded p-3 space-y-3">
      <div class="flex items-center justify-between">
        <div class="font-semibold">Canasta de picking</div>
        <div class="text-sm">
          @if($basket)
            Estado: <span class="px-2 py-0.5 rounded bg-gray-100">{{ $basket->status }}</span>
            @if($basket->responsibleUser)
              <span class="ml-2">Responsable: <strong>{{ $basket->responsibleUser->name }}</strong></span>
            @endif
          @else
            <span class="text-gray-500">No hay canasta aún.</span>
          @endif
        </div>
      </div>

      @if(!$basket)
        {{-- Crear y asignarme canasta --}}
        <form method="POST" action="{{ route('admin.orders.basket.open', $order) }}">
          @csrf
          <button class="bg-blue-600 text-white px-3 py-1 rounded">Crear y asignarme canasta</button>
        </form>
      @else
        {{-- ==== Transferencia (con reglas UX) ==== --}}
        <div class="flex flex-col gap-2">
          <form id="transfer-form"
                method="POST"
                action="{{ route('admin.baskets.transfer.create', $basket) }}"
                class="flex flex-wrap gap-2 items-center">
            @csrf

            <div class="flex items-center gap-2">
              <label class="text-sm text-gray-700">Derivar a:</label>
              <input
                type="text"
                id="user_lookup"
                class="border p-1 w-64"
                placeholder="ID o nombre/email del usuario"
                list="users_datalist"
                autocomplete="off"
                @disabled(!$canTransfer)
              >
              <datalist id="users_datalist">
                @foreach($activeUsers as $u)
                  <option value="{{ $u->id }} — {{ $u->name }} ({{ $u->email }})"></option>
                @endforeach
              </datalist>
            </div>

            <input type="hidden" name="to_user_id" id="to_user_id" value="">
            <input type="text" name="note" class="border p-1 w-64" placeholder="Nota (opcional)" @disabled(!$canTransfer)>
            <button id="btn-transfer" class="px-3 py-1 rounded border bg-white hover:bg-gray-50" @disabled(!$canTransfer)>
              Transferir
            </button>
          </form>

          {{-- Cerrar canasta: solo si soy responsable y está open --}}
          @if(!$readOnly && in_array($basket->status, ['open','in_progress'], true))
            <form method="POST" action="{{ route('admin.baskets.close', $basket) }}">
              @csrf
              <button class="px-3 py-1 rounded border bg-gray-800 text-white">Cerrar canasta</button>
            </form>
          @endif


          @if(!$canTransfer)
            <div class="text-xs text-gray-500">
              @if(!$basket)
                No hay canasta creada.
              @elseif($basket->status !== 'open')
                La canasta no está abierta para transferir.
              @elseif((int)$basket->responsible_user_id !== (int)$meId)
                Vista de solo lectura — Responsable: <strong>{{ $basket->responsibleUser?->name }}</strong>.
              @else
                Ya existe una transferencia pendiente.
              @endif
            </div>
          @endif
        </div>

        {{-- Ítems para pick/unpick con canasta --}}
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white border mt-2">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 border">Producto</th>
                <th class="p-2 border">Variante</th>
                <th class="p-2 border">Solicitado</th>
                <th class="p-2 border">Pickeado</th>
                <th class="p-2 border">Backorder</th>
                <th class="p-2 border">Pick</th>
                <th class="p-2 border">Unpick</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->items as $it)
                @php
                  $variant = \App\Models\ProductVariant::with(['product','color','size'])->find($it->variant_id);
                  $pending = max(0, (int)$it->qty - (int)$it->picked_qty);
                @endphp
                <tr>
                  <td class="p-2 border">{{ $variant?->product?->name }}</td>
                  <td class="p-2 border">{{ $variant?->color?->name }} / {{ $variant?->size?->code }}</td>
                  <td class="p-2 border text-center">{{ (int)$it->qty }}</td>
                  <td class="p-2 border text-center">{{ (int)$it->picked_qty }}</td>
                  <td class="p-2 border text-center">
                    {{ (int)($it->backorder_qty ?? 0) }}
                    @if((int)($it->backorder_qty ?? 0) > 0)
                      <span class="ml-1 text-xs px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">Backorder</span>
                    @endif
                  </td>

                  {{-- Pick --}}
                  <td class="p-2 border">
                    <form method="POST" action="{{ route('admin.baskets.pick', $basket) }}" class="flex items-center gap-2">
                      @csrf
                      <input type="hidden" name="order_item_id" value="{{ $it->id }}">
                      <input type="number"
                             name="qty"
                             min="1"
                             max="{{ $pending }}"
                             value="{{ $pending > 0 ? 1 : 0 }}"
                             class="border p-1 w-20"
                             @disabled($pending<=0 || $readOnly)
                      >
                      <button class="px-2 py-1 rounded text-sm border" @disabled($pending<=0 || $readOnly)>Pickear</button>
                    </form>
                  </td>

                  {{-- Unpick --}}
                  <td class="p-2 border">
                    <form method="POST" action="{{ route('admin.baskets.unpick', $basket) }}" class="flex items-center gap-2">
                      @csrf
                      <input type="hidden" name="order_item_id" value="{{ $it->id }}">
                      <input type="number"
                             name="qty"
                             min="1"
                             max="{{ (int)$it->picked_qty }}"
                             value="{{ (int)$it->picked_qty > 0 ? 1 : 0 }}"
                             class="border p-1 w-20"
                             @disabled($it->picked_qty<=0 || $readOnly)
                      >
                      <button class="px-2 py-1 rounded text-sm border" @disabled($it->picked_qty<=0 || $readOnly)>Devolver</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    {{-- Ítems (resumen y acciones por ítem) --}}
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
            <th class="p-2 border">Backorder</th>
            <th class="p-2 border">Stock</th>
            <th class="p-2 border">Acciones</th>
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
              <td class="p-2 border">{{ (int)$it->qty }}</td>
              <td class="p-2 border">S/ {{ number_format($it->amount,2) }}</td>
              <td class="p-2 border">
                @if((int)$it->backorder_qty > 0)
                  <span class="px-2 py-1 text-yellow-800 bg-yellow-100 rounded">{{ (int)$it->backorder_qty }}</span>
                @else
                  <span class="px-2 py-1 text-green-800 bg-green-100 rounded">OK</span>
                @endif
              </td>
              <td class="p-2 border">{{ (int)($variant->stock ?? 0) }}</td>
              <td class="p-2 border">
                @if((int)$it->backorder_qty > 0)
                  <form method="POST" action="{{ route('admin.orders.items.pick', [$order, $it]) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="number" name="qty" min="1" max="{{ (int)$it->backorder_qty }}" value="{{ (int)$it->backorder_qty }}" class="border p-1 w-20" @disabled($readOnly)>
                    <button class="bg-blue-600 text-white px-2 py-1 rounded text-sm" @disabled($readOnly)>Pick</button>
                  </form>
                @endif

                @if(((int)$it->qty - (int)$it->backorder_qty) > 0)
                  <form method="POST" action="{{ route('admin.orders.items.unpick', [$order, $it]) }}" class="flex items-center gap-2 mt-2">
                    @csrf
                    <input type="number" name="qty" min="1" max="{{ (int)$it->qty - (int)$it->backorder_qty }}" value="1" class="border p-1 w-20" @disabled($readOnly)>
                    <button class="bg-gray-600 text-white px-2 py-1 rounded text-sm" @disabled($readOnly)>Unpick</button>
                  </form>
                @endif
              </td>
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
    {{-- Pago --}}
    <div class="border rounded p-3">
      <div class="font-semibold mb-2">Actualizar pago</div>
      <form method="POST" action="{{ route('admin.orders.paystatus',$order) }}" class="flex gap-2">
        @csrf
        <select name="payment_status" class="border p-2" @disabled($readOnly)>
          @foreach(['unpaid','pending_confirmation','cod_promised','authorized','paid','failed','partially_paid'] as $ps)
            <option value="{{ $ps }}" @selected($ps===$order->payment_status)>{{ $ps }}</option>
          @endforeach
        </select>
        <button class="bg-gray-800 text-white px-3 rounded" @disabled($readOnly)>Guardar</button>
      </form>
    </div>
      <div class="border rounded p-3">
  <div class="font-semibold mb-2">Registrar nuevo pago</div>
  <form method="POST" action="{{ route('admin.orders.payments.store', $order) }}" enctype="multipart/form-data" class="space-y-2">
    @csrf
    <div>
      <label class="block text-sm">Método</label>
      <input type="text" name="method" class="border p-2 w-full" placeholder="Transferencia / Yape / Plin" required>
    </div>
    <div>
      <label class="block text-sm">Monto (S/)</label>
      <input type="number" step="0.01" name="amount" class="border p-2 w-full" required>
    </div>
    <div>
      <label class="block text-sm">Referencia bancaria</label>
      <input type="text" name="provider_ref" class="border p-2 w-full">
    </div>
    <div>
      <label class="block text-sm">Comprobante (imagen o PDF)</label>
      <input type="file" name="evidence" class="border p-2 w-full" accept=".jpg,.jpeg,.png,.pdf">
    </div>
    <button class="bg-blue-600 text-white px-3 py-1 rounded">Registrar Pago</button>
  </form>
</div>
{{-- === Pagos registrados === --}}
@if($order->payments->count())
  <div class="border rounded p-3 mt-4">
    <div class="font-semibold mb-2">Pagos registrados</div>
    <table class="min-w-full bg-white border">
      <thead class="bg-gray-50">
        <tr>
          <th class="p-2 border">#</th>
          <th class="p-2 border">Método</th>
          <th class="p-2 border">Monto</th>
          <th class="p-2 border">Referencia</th>
          <th class="p-2 border">Comprobante</th>
          <th class="p-2 border">Estado</th>
          <th class="p-2 border">Registrado por</th>
          <th class="p-2 border">Acción</th>
        </tr>
      </thead>
      <tbody>
        @foreach($order->payments as $p)
          <tr>
            <td class="p-2 border text-center">{{ $p->id }}</td>
            <td class="p-2 border">{{ $p->method }}</td>
            <td class="p-2 border text-right">S/ {{ number_format($p->amount, 2) }}</td>
            <td class="p-2 border text-xs text-gray-600">{{ $p->provider_ref ?? '—' }}</td>
            <td class="p-2 border text-center">
              @if($p->evidence_url)
                <a href="{{ $p->evidence_url }}" target="_blank" class="text-blue-600 underline">Ver</a>
              @else
                —
              @endif
            </td>
            <td class="p-2 border">
              <span class="px-2 py-1 text-xs rounded 
                @if($p->status === 'paid') bg-green-100 text-green-800
                @elseif($p->status === 'pending_confirmation') bg-yellow-100 text-yellow-800
                @elseif($p->status === 'failed') bg-red-100 text-red-800
                @else bg-gray-100 text-gray-800 @endif">
                {{ $p->status }}
              </span>
            </td>
            <td class="p-2 border text-sm text-gray-600">
              {{ $p->collectedBy?->name ?? '—' }}<br>
              <span class="text-xs">{{ $p->collected_at?->format('d/m/Y H:i') ?? '' }}</span>
            </td>
            <td class="p-2 border text-center">
              @if(auth()->user()->hasAnyRole(['admin', 'vendedor']))
                <form method="POST" action="{{ route('admin.orders.payments.status', $p) }}">
                  @csrf
                  <select name="status" class="border p-1 text-sm">
                    @foreach(['pending_confirmation','authorized','paid','failed','partially_paid','refunded'] as $status)
                      <option value="{{ $status }}" @selected($p->status === $status)>
                        {{ ucfirst(str_replace('_',' ',$status)) }}
                      </option>
                    @endforeach
                  </select>
                  <button class="ml-2 bg-gray-800 text-white px-2 py-1 rounded text-xs">Actualizar</button>
                </form>
              @else
                <span class="text-xs text-gray-400">Sin permiso</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

    {{-- Estado pedido --}}
    <div class="border rounded p-3">
      <div class="font-semibold mb-2">Actualizar pedido</div>
      <form method="POST" action="{{ route('admin.orders.status',$order) }}" class="flex gap-2">
        @csrf
        <select name="status" class="border p-2" @disabled($readOnly)>
          @foreach(['new','confirmed','preparing','shipped','delivered','cancelled'] as $s)
            <option value="{{ $s }}" @selected($s===$order->status)>{{ $s }}</option>
          @endforeach
        </select>
        <button class="bg-gray-800 text-white px-3 rounded" @disabled($readOnly)>Guardar</button>
      </form>
    </div>

    {{-- Prioridad --}}
    <div class="border rounded p-3">
      <div class="font-semibold mb-2">Prioridad de atención</div>

      <div class="mb-2">
        @if($order->is_priority)
        <span class="text-xs px-2 py-1 rounded bg-amber-200 text-amber-800">
          Prioritario ({{ (int)$order->priority_level }})
        </span>
        @else
        <span class="text-xs px-2 py-1 rounded bg-gray-200 text-gray-700">
          Sin prioridad
        </span>
        @endif
      </div>

      @can('orders.update')
      <form method="POST" action="{{ route('admin.orders.priority', $order) }}" class="space-y-2">
        @csrf
        <div class="flex items-center gap-2">
          <label class="text-sm">Prioritario</label>
          <select name="is_priority" class="border p-1" @disabled($readOnly)>
            <option value="0" @selected(!$order->is_priority)>No</option>
            <option value="1" @selected($order->is_priority)>Sí</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <label class="text-sm">Nivel</label>
          <input type="number" name="priority_level" min="0" max="99" class="border p-1 w-24"
                 value="{{ (int)$order->priority_level }}" @disabled($readOnly)>
          <span class="text-xs text-gray-500">0 = sin prioridad</span>
        </div>

        <div class="flex gap-2">
          <button class="bg-gray-800 text-white px-3 rounded text-sm" @disabled($readOnly)>Guardar</button>
        </div>
      </form>

      <div class="flex gap-2 mt-2">
        <form method="POST" action="{{ route('admin.orders.priority', $order) }}">
          @csrf
          <input type="hidden" name="action" value="raise">
          <button class="bg-amber-600 text-white px-3 rounded text-sm" @disabled($readOnly)>Subir +1</button>
        </form>
        <form method="POST" action="{{ route('admin.orders.priority', $order) }}">
          @csrf
          <input type="hidden" name="action" value="lower">
          <button class="bg-amber-200 text-amber-900 px-3 rounded text-sm" @disabled($readOnly)>Bajar -1</button>
        </form>
        <form method="POST" action="{{ route('admin.orders.priority', $order) }}">
          @csrf
          <input type="hidden" name="action" value="toggle">
          <button class="bg-gray-200 text-gray-900 px-3 rounded text-sm" @disabled($readOnly)>
            {{ $order->is_priority ? 'Quitar prioridad' : 'Marcar prioritario' }}
          </button>
        </form>
      </div>
      @endcan
    </div>

    {{-- Liquidación de envío --}}
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

        <form method="POST" action="{{ route('admin.orders.shippingActual', $order) }}" class="flex items-end gap-2 mb-2">
          @csrf
          <div class="flex-1">
            <label class="block text-sm text-gray-600">Costo real courier</label>
            <input type="number" step="0.01" min="0" name="shipping_actual"
                   value="{{ old('shipping_actual', $order->shipping_actual) }}"
                   class="border p-2 w-full" id="shipping_actual" @disabled($readOnly)>
          </div>
          <button class="bg-gray-800 text-white px-3 py-2 rounded" @disabled($readOnly)>Guardar</button>
        </form>

        @php
          $__diff = is_null($order->shipping_actual)
            ? null
            : round($order->shipping_amount - $order->shipping_actual, 2);
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
          <form method="POST" action="{{ route('admin.orders.settlement.refund', $order) }}">
            @csrf
            <button class="px-3 py-2 bg-emerald-600 text-white rounded"
                    @disabled(is_null($order->shipping_actual) || ($__diff ?? 0) <= 0 || $readOnly)>
              Registrar reembolso
            </button>
          </form>

          <form method="POST" action="{{ route('admin.orders.settlement.charge', $order) }}">
            @csrf
            <button class="px-3 py-2 bg-orange-600 text-white rounded"
                    @disabled(is_null($order->shipping_actual) || ($__diff ?? 0) >= 0 || $readOnly)>
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

    {{-- Flash feedback --}}
    @if(session('success'))
      <div class="bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="bg-red-100 text-red-800 p-2 rounded">{{ session('error') }}</div>
    @endif
  </div>
</div>

{{-- ===== JS: cálculo diff + transferencia con input predictivo ===== --}}
<script>
(function () {
  // --- Diff envío
  const input = document.getElementById('shipping_actual');
  if (input) {
    const deposited = parseFloat("{{ $order->shipping_amount !== null ? number_format((float)$order->shipping_amount, 2, '.', '') : 0 }}") || 0;
    const diffValue = document.getElementById('diff_value');
    const diffHint  = document.getElementById('diff_hint');
    input.addEventListener('input', () => {
      const v = parseFloat(input.value || '0');
      if (isNaN(v)) return;
      const d = (deposited - v);
      diffValue.textContent = 'S/ ' + d.toFixed(2);
      if (d > 0) diffHint.textContent = '→ Reembolso sugerido';
      else if (d < 0) diffHint.textContent = '→ Cobro adicional sugerido';
      else diffHint.textContent = 'Sin diferencia';
    });
  }

  // --- Transferencia con predictivo (solo si existe el form)
  const meId   = {{ (int)($meId ?? 0) }};
  const users  = {!! json_encode($jsUsers ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};

  const form     = document.getElementById('transfer-form');
  const lookup   = document.getElementById('user_lookup');
  const hiddenId = document.getElementById('to_user_id');
  const btn      = document.getElementById('btn-transfer');

  if (form && lookup && hiddenId && btn) {
    function findUserIdFromInput(val) {
      val = (val || '').trim();
      if (!val) return null;
      if (/^\d+$/.test(val)) {
        const id = parseInt(val, 10);
        return users.some(u => u.id === id) ? id : null;
      }
      const m = val.match(/^(\d+)\s+—/);
      if (m) {
        const id = parseInt(m[1], 10);
        return users.some(u => u.id === id) ? id : null;
      }
      const L = val.toLowerCase();
      const hit = users.find(u =>
        (u.name && u.name.toLowerCase().includes(L)) ||
        (u.email && u.email.toLowerCase().includes(L))
      );
      return hit ? hit.id : null;
    }

    function validate() {
      const candidateId = findUserIdFromInput(lookup.value);
      hiddenId.value = candidateId ?? '';
      const invalid = !candidateId || candidateId === meId;
      btn.disabled = invalid;
      btn.title = invalid
        ? (candidateId === meId ? 'No puedes derivarte la canasta a ti mismo.' : 'Selecciona un usuario válido.')
        : '';
    }

    lookup.addEventListener('input', validate);
    form.addEventListener('submit', (e) => {
      validate();
      const id = hiddenId.value ? parseInt(hiddenId.value, 10) : null;
      if (!id) {
        e.preventDefault();
        alert('Selecciona un usuario válido (escribe ID o busca por nombre/email y elige de la lista).');
        return;
      }
      if (id === meId) {
        e.preventDefault();
        alert('No puedes derivarte la canasta a ti mismo.');
        return;
      }
    });
    validate();
  }
})();
</script>
@endsection
