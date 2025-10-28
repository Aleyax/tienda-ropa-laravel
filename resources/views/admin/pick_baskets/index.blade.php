@extends('admin.layout')

@section('title','Canastas de picking')

@section('content')
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Canastas ({{ $status }})</h1>
    <a href="{{ route('admin.pick_baskets.create') }}" class="px-3 py-2 bg-blue-600 text-white rounded">Nueva canasta</a>
  </div>

  <form method="GET" class="mb-3">
    <select name="status" class="border p-2">
      @foreach(['open','in_progress','closed','cancelled'] as $st)
        <option value="{{ $st }}" @selected($status===$st)>{{ $st }}</option>
      @endforeach
    </select>
    <button class="px-3 py-2 border rounded">Filtrar</button>
  </form>

  <div class="overflow-x-auto">
    <table class="min-w-full bg-white border">
      <thead>
        <tr class="bg-gray-50">
          <th class="p-2 border">ID</th>
          <th class="p-2 border">Pedido</th>
          <th class="p-2 border">Almacén</th>
          <th class="p-2 border">Responsable</th>
          <th class="p-2 border">Estado</th>
          <th class="p-2 border">Creado</th>
          <th class="p-2 border">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($baskets as $b)
          <tr>
            <td class="p-2 border">#{{ $b->id }}</td>
            <td class="p-2 border">
              @if($b->order)
                <a class="text-blue-600 underline" href="{{ route('admin.orders.show', $b->order) }}">
                  Pedido #{{ $b->order->id }}
                </a>
              @else
                <span class="text-gray-500">—</span>
              @endif
            </td>
            <td class="p-2 border">{{ $b->warehouse?->name }}</td>
            <td class="p-2 border">{{ $b->responsibleUser?->name ?? '—' }}</td>
            <td class="p-2 border"><span class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $b->status }}</span></td>
            <td class="p-2 border text-sm">{{ $b->created_at?->format('Y-m-d H:i') }}</td>
            <td class="p-2 border">
              <a class="text-blue-600 underline" href="{{ route('admin.pick_baskets.show', $b) }}">Ver</a>
            </td>
          </tr>
        @empty
          <tr><td class="p-2 border" colspan="7">No hay canastas.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3">
    {{ $baskets->links() }}
  </div>
@endsection
