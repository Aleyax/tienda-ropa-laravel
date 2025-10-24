@extends('admin.layout')

@section('title','Pedidos')

@section('content')
  <form method="GET" class="flex flex-wrap gap-2 mb-4">
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

    <button class="bg-gray-800 text-white px-3 rounded">Filtrar</button>
    <a href="{{ route('admin.orders.index') }}" class="px-3 border rounded">Limpiar</a>
  </form>

  <div class="overflow-x-auto">
    <table class="min-w-full bg-white border">
      <thead>
        <tr class="bg-gray-50">
          <th class="p-2 border">ID</th>
          <th class="p-2 border">Cliente</th>
          <th class="p-2 border">Total</th>
          <th class="p-2 border">Método</th>
          <th class="p-2 border">Pago</th>
          <th class="p-2 border">Pedido</th>
          <th class="p-2 border">Creado</th>
          <th class="p-2 border">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $o)
          <tr>
            <td class="p-2 border">#{{ $o->id }}</td>
            <td class="p-2 border">{{ $o->user?->name ?? 'Invitado' }}<br><span class="text-xs text-gray-500">{{ $o->user?->email }}</span></td>
            <td class="p-2 border">S/ {{ number_format($o->total,2) }}</td>
            <td class="p-2 border">{{ $o->payment_method }}</td>
            <td class="p-2 border"><span class="text-xs px-2 py-1 rounded bg-gray-100">{{ $o->payment_status }}</span></td>
            <td class="p-2 border"><span class="text-xs px-2 py-1 rounded bg-gray-100">{{ $o->status }}</span></td>
            <td class="p-2 border text-sm">{{ $o->created_at->format('Y-m-d H:i') }}</td>
            <td class="p-2 border">
              <a class="text-blue-600" href="{{ route('admin.orders.show',$o) }}">Ver</a>
            </td>
          </tr>
        @empty
          <tr><td class="p-2 border" colspan="8">No hay pedidos.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3">{{ $orders->links() }}</div>
@endsection
