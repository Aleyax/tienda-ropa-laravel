@extends('admin.layout')

@section('title','Nueva canasta')

@section('content')
  <h1 class="text-xl font-semibold mb-4">Crear canasta</h1>

  @if(session('error'))
    <div class="mb-3 bg-red-100 text-red-800 p-2 rounded">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-3 bg-red-100 text-red-800 p-2 rounded">
      @foreach($errors->all() as $e)
        <div>{{ $e }}</div>
      @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('admin.pick_baskets.store') }}" class="space-y-4">
    @csrf

    <div>
      <label class="block text-sm font-semibold mb-1">Pedido (opcional)</label>
      <select name="order_id" class="border p-2 w-full">
        <option value="">— Sin pedido asociado —</option>
        @foreach($orders as $o)
          <option value="{{ $o->id }}" @selected($orderId==$o->id)>
            #{{ $o->id }} — {{ $o->user?->name ?? 'Invitado' }} — {{ $o->created_at?->format('Y-m-d H:i') }}
          </option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1">Almacén</label>
      <select name="warehouse_id" class="border p-2 w-full" required>
        @foreach($warehouses as $w)
          <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
        @endforeach
      </select>
    </div>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">Crear</button>
  </form>
@endsection
