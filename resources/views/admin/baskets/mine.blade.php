@extends('admin.layout')

@section('title','Mis canastas')

@section('content')
<div class="space-y-6">

  {{-- Transferencias pendientes para aceptar --}}
  <div class="border rounded">
    <div class="p-3 font-semibold border-b">Transferencias pendientes para mí</div>
    @if($pendingTransfers->isEmpty())
      <div class="p-3 text-sm text-gray-600">No tienes transferencias pendientes.</div>
    @else
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
          <thead class="bg-gray-50">
            <tr>
              <th class="p-2 border">#</th>
              <th class="p-2 border">Pedido</th>
              <th class="p-2 border">De</th>
              <th class="p-2 border">Nota</th>
              <th class="p-2 border">Fecha</th>
              <th class="p-2 border">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @foreach($pendingTransfers as $t)
              <tr>
                <td class="p-2 border">{{ $t->id }}</td>
                <td class="p-2 border">
                  @if($t->basket && $t->basket->order)
                    <a class="text-blue-600 underline" href="{{ route('admin.orders.show', $t->basket->order_id) }}">
                      Pedido #{{ $t->basket->order_id }}
                    </a>
                    <div class="text-xs text-gray-500">
                      Cliente: {{ $t->basket->order->user?->name ?? 'Invitado' }}
                    </div>
                  @else
                    —
                  @endif
                </td>
                <td class="p-2 border">{{ $t->fromUser?->name ?? '—' }}</td>
                <td class="p-2 border text-sm">{{ $t->note ?? '—' }}</td>
                <td class="p-2 border text-sm">{{ $t->created_at?->format('Y-m-d H:i') }}</td>
                <td class="p-2 border">
                  <form method="POST" action="{{ route('admin.baskets.transfer.accept', $t) }}" class="inline">
                    @csrf
                    <button class="px-3 py-1 bg-emerald-600 text-white rounded text-sm">Aceptar</button>
                  </form>
                  <form method="POST" action="{{ route('admin.baskets.transfer.decline', $t) }}" class="inline ml-2">
                    @csrf
                    <button class="px-3 py-1 bg-red-600 text-white rounded text-sm">Declinar</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Mis canastas activas (responsable = yo) --}}
  <div class="border rounded">
    <div class="p-3 font-semibold border-b">Mis canastas activas</div>
    @if($myOpenBaskets->isEmpty())
      <div class="p-3 text-sm text-gray-600">No tienes canastas abiertas o en progreso.</div>
    @else
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
          <thead class="bg-gray-50">
            <tr>
              <th class="p-2 border">#</th>
              <th class="p-2 border">Pedido</th>
              <th class="p-2 border">Estado</th>
              <th class="p-2 border">Cliente</th>
              <th class="p-2 border">Actualizado</th>
              <th class="p-2 border">Ir</th>
            </tr>
          </thead>
          <tbody>
            @foreach($myOpenBaskets as $b)
              <tr>
                <td class="p-2 border">{{ $b->id }}</td>
                <td class="p-2 border">
                  @if($b->order)
                    <a class="text-blue-600 underline" href="{{ route('admin.orders.show', $b->order_id) }}">
                      Pedido #{{ $b->order_id }}
                    </a>
                  @else
                    #{{ $b->order_id }}
                  @endif
                </td>
                <td class="p-2 border">
                  <span class="px-2 py-0.5 text-xs rounded bg-gray-100">{{ $b->status }}</span>
                </td>
                <td class="p-2 border">{{ $b->order?->user?->name ?? 'Invitado' }}</td>
                <td class="p-2 border text-sm">{{ $b->updated_at?->format('Y-m-d H:i') }}</td>
                <td class="p-2 border">
                  <a class="px-3 py-1 border rounded text-sm" href="{{ route('admin.orders.show', $b->order_id) }}">Ver</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

</div>
@endsection
