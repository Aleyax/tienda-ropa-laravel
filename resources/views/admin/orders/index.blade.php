@extends('admin.layout')

@section('title','Pedidos')

@section('content')

  {{-- ALERTAS DE PENDIENTES POR TIPO --}}
  @if(($pendingRetail ?? 0) > 0)
    <div class="mb-3 p-2 rounded bg-yellow-100 text-yellow-800">
      Hay {{ $pendingRetail }} pedidos <strong>minoristas</strong> pendientes de picking.
      <a class="underline" href="{{ route('admin.orders.index', array_merge(request()->query(), ['type'=>'retail','pending'=>1])) }}">
        Ver
      </a>
    </div>
  @endif

  @if(($pendingWholesale ?? 0) > 0)
    <div class="mb-3 p-2 rounded bg-blue-100 text-blue-800">
      Hay {{ $pendingWholesale }} pedidos <strong>mayoristas</strong> pendientes de picking.
      <a class="underline" href="{{ route('admin.orders.index', array_merge(request()->query(), ['type'=>'wholesale','pending'=>1])) }}">
        Ver
      </a>
    </div>
  @endif

  {{-- FILTROS --}}
  <form method="GET" class="grid md:grid-cols-6 gap-2 mb-4">
    <input type="date" name="from" value="{{ request('from') }}" class="border p-2" placeholder="Desde">
    <input type="date" name="to"   value="{{ request('to')   }}" class="border p-2" placeholder="Hasta">

    <input type="text" name="email" value="{{ request('email') }}" class="border p-2 md:col-span-2" placeholder="Cliente (email)">

    <select name="method" class="border p-2">
      <option value="">Método</option>
      @foreach($methods as $m)
        <option value="{{ $m }}" @selected(request('method')===$m)>{{ $m }}</option>
      @endforeach
    </select>

    <select name="pstatus" class="border p-2">
      <option value="">Pago</option>
      @foreach($pstatuses as $ps)
        <option value="{{ $ps }}" @selected(request('pstatus')===$ps)>{{ $ps }}</option>
      @endforeach
    </select>

    <select name="status" class="border p-2">
      <option value="">Pedido</option>
      @foreach($statuses as $s)
        <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
      @endforeach
    </select>

    {{-- FILTROS NUEVOS: tipo y pendientes --}}
    <div class="md:col-span-6 flex flex-wrap gap-2 items-center">
      <select name="type" class="border p-2">
        <option value="">(Todos los tipos)</option>
        <option value="retail"    @selected(request('type')==='retail')>Minorista</option>
        <option value="wholesale" @selected(request('type')==='wholesale')>Mayorista</option>
      </select>

      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="pending" value="1" {{ request('pending') ? 'checked' : '' }}>
        <span>Solo pendientes (con backorder)</span>
      </label>
    </div>

    <div class="md:col-span-6 flex gap-2">
      <button class="bg-gray-800 text-white px-3 rounded">Filtrar</button>
      <a href="{{ route('admin.orders.index') }}" class="px-3 border rounded">Limpiar</a>
      <a href="{{ route('admin.orders.export', request()->query()) }}" class="px-3 border rounded">Exportar CSV</a>
    </div>
  </form>

  {{-- ACCIONES MASIVAS --}}
  <div class="flex flex-wrap gap-3 mb-2">
    @can('orders.update')
    <form method="POST" action="{{ route('admin.orders.bulkStatus') }}" class="flex gap-2 items-center">
      @csrf
      <input type="hidden" name="ids" id="ids_status">
      <select name="status" class="border p-2">
        @foreach($statuses as $s)
          <option value="{{ $s }}">{{ $s }}</option>
        @endforeach
      </select>
      <button class="bg-blue-600 text-white px-3 rounded text-sm">Cambiar estado (seleccionados)</button>
    </form>
    @endcan

    @can('payments.validate')
    <form method="POST" action="{{ route('admin.orders.bulkPayStatus') }}" class="flex gap-2 items-center">
      @csrf
      <input type="hidden" name="ids" id="ids_pay">
      <select name="payment_status" class="border p-2">
        @foreach($pstatuses as $ps)
          <option value="{{ $ps }}">{{ $ps }}</option>
        @endforeach
      </select>
      <button class="bg-green-600 text-white px-3 rounded text-sm">Cambiar pago (seleccionados)</button>
    </form>
    @endcan
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full bg-white border">
      <thead>
        <tr class="bg-gray-50">
          <th class="p-2 border"><input type="checkbox" id="chk_all"></th>
          <th class="p-2 border">ID</th>
          <th class="p-2 border">Tipo</th>
          <th class="p-2 border">Cliente</th>
          <th class="p-2 border">Total</th>
          <th class="p-2 border">Método</th>
          <th class="p-2 border">Pago</th>
          <th class="p-2 border">Pedido</th>
          <th class="p-2 border">Prioridad</th>
          <th class="p-2 border">Creado</th>
          <th class="p-2 border">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $o)
          <tr>
            <td class="p-2 border">
              <input type="checkbox" class="chk_row" value="{{ $o->id }}">
            </td>
            <td class="p-2 border">#{{ $o->id }}</td>

            {{-- Tipo (retail / wholesale) --}}
            <td class="p-2 border">
              @if($o->order_type === 'retail')
                <span class="text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-700">Minorista</span>
              @else
                <span class="text-xs px-2 py-1 rounded bg-indigo-100 text-indigo-700">Mayorista</span>
              @endif
            </td>

            <td class="p-2 border">
              {{ $o->user?->name ?? 'Invitado' }}
              <br>
              <span class="text-xs text-gray-500">{{ $o->user?->email }}</span>
            </td>

            <td class="p-2 border">S/ {{ number_format($o->total,2) }}</td>
            <td class="p-2 border">{{ $o->payment_method }}</td>
            <td class="p-2 border">
              <span class="text-xs px-2 py-1 rounded bg-gray-100">
                {{ $o->payment_status }}
              </span>
            </td>
            <td class="p-2 border">
              <span class="text-xs px-2 py-1 rounded bg-gray-100">
                {{ $o->status }}
              </span>
            </td>

            {{-- Prioridad --}}
            <td class="p-2 border">
              @if($o->is_priority)
                <span class="text-xs px-2 py-1 rounded bg-amber-200 text-amber-800">
                  Prioridad {{ (int)$o->priority_level }}
                </span>
              @else
                <span class="text-xs text-gray-400">—</span>
              @endif
            </td>

            <td class="p-2 border text-sm">{{ $o->created_at->format('Y-m-d H:i') }}</td>
            <td class="p-2 border">
              <a class="text-blue-600" href="{{ route('admin.orders.show',$o) }}">Ver</a>
            </td>
          </tr>
        @empty
          <tr>
            <td class="p-2 border" colspan="11">No hay pedidos.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3 flex justify-between items-center">
    <div class="text-sm text-gray-600">
      Total (página): <strong>S/ {{ number_format($pageTotal,2) }}</strong>
      |
      Total (filtro): <strong>S/ {{ number_format($filterTotal,2) }}</strong>
    </div>
    <div>{{ $orders->links() }}</div>
  </div>

  <script>
    const all = document.getElementById('chk_all');
    const rows = document.querySelectorAll('.chk_row');
    const idsStatus = document.getElementById('ids_status');
    const idsPay = document.getElementById('ids_pay');

    all?.addEventListener('change', () => rows.forEach(r => r.checked = all.checked));

    function syncIds() {
      const ids = [...rows].filter(r => r.checked).map(r => r.value);
      idsStatus.value = JSON.stringify(ids);
      idsPay.value = JSON.stringify(ids);
    }
    document.addEventListener('change', (e) => {
      if (e.target.matches('#chk_all, .chk_row')) syncIds();
    });
    syncIds();
  </script>
@endsection
