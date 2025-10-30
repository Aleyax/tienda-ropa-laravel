@extends('admin.layout')

@section('title','Transferencias de Canastas')

@section('content')
  <h1 class="text-xl font-semibold mb-3">Transferencias de Canastas</h1>

  @if(session('success'))
    <div class="bg-green-100 text-green-800 p-2 rounded mb-2">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="bg-red-100 text-red-800 p-2 rounded mb-2">{{ session('error') }}</div>
  @endif

  <table class="min-w-full bg-white border">
    <thead class="bg-gray-50">
      <tr>
        <th class="p-2 border">Pedido</th>
        <th class="p-2 border">De</th>
        <th class="p-2 border">Para</th>
        <th class="p-2 border">Nota</th>
        <th class="p-2 border">Estado</th>
        <th class="p-2 border">Acciones</th>
      </tr>
    </thead>
    <tbody>
      @forelse($transfers as $t)
      <tr>
        <td class="p-2 border">#{{ $t->basket->order_id }}</td>
        <td class="p-2 border">{{ $t->fromUser?->name ?? '—' }}</td>
        <td class="p-2 border">{{ $t->toUser?->name ?? '—' }}</td>
        <td class="p-2 border">{{ $t->note ?? '—' }}</td>
        <td class="p-2 border capitalize">{{ $t->status }}</td>
        <td class="p-2 border text-center">
          @if($t->status === 'pending' && $t->to_user_id === auth()->id())
            <form action="{{ route('admin.baskets.transfer.accept', $t) }}" method="POST" class="inline">@csrf
              <button class="bg-green-600 text-white px-2 py-1 rounded text-sm">Aceptar</button>
            </form>
            <form action="{{ route('admin.baskets.transfer.decline', $t) }}" method="POST" class="inline">@csrf
              <button class="bg-red-600 text-white px-2 py-1 rounded text-sm">Rechazar</button>
            </form>
          @elseif($t->status === 'pending' && $t->from_user_id === auth()->id())
            <form action="{{ route('admin.baskets.transfer.cancel', $t) }}" method="POST" class="inline">@csrf
              <button class="bg-gray-600 text-white px-2 py-1 rounded text-sm">Cancelar</button>
            </form>
          @else
            <span class="text-xs text-gray-500">Sin acciones</span>
          @endif
        </td>
      </tr>
      @empty
        <tr><td colspan="6" class="p-3 text-center text-gray-500">No hay transferencias.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="mt-3">{{ $transfers->links() }}</div>
@endsection
