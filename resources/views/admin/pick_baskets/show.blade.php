@extends('admin.layout')

@section('title',"Canasta #{$basket->id}")

@section('content')
  @if(session('success'))
    <div class="mb-3 bg-green-100 text-green-800 p-2 rounded">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="mb-3 bg-red-100 text-red-800 p-2 rounded">{{ session('error') }}</div>
  @endif

  <div class="grid md:grid-cols-3 gap-4">
    <div class="md:col-span-2 space-y-4">
      <div class="border rounded">
        <div class="p-3 font-semibold border-b">Resumen</div>
        <div class="p-3 grid grid-cols-2 gap-2">
          <div><span class="text-gray-500">ID:</span> #{{ $basket->id }}</div>
          <div><span class="text-gray-500">Estado:</span> {{ $basket->status }}</div>

          <div><span class="text-gray-500">Pedido:</span>
            @if($basket->order)
              <a class="text-blue-600 underline" href="{{ route('admin.orders.show', $basket->order) }}">
                #{{ $basket->order->id }}
              </a>
            @else
              —
            @endif
          </div>
          <div><span class="text-gray-500">Almacén:</span> {{ $basket->warehouse?->name }}</div>

          <div><span class="text-gray-500">Responsable:</span> {{ $basket->responsibleUser?->name ?? '—' }}</div>
          <div><span class="text-gray-500">Creado por:</span> {{ $basket->createdByUser?->name ?? '—' }}</div>
        </div>
      </div>

      {{-- Aquí más adelante: lista de Ítems pickeados, botones para agregar/quitar, etc. --}}
      <div class="border rounded">
        <div class="p-3 font-semibold border-b">Ítems (próximo paso)</div>
        <div class="p-3 text-sm text-gray-500">
          En el siguiente paso implementamos: agregar ítems a la canasta desde el pedido y/o inventario del almacén, y marcar picked/unpicked.
        </div>
      </div>
    </div>

    <div class="space-y-4">
      {{-- Acciones rápidas (proximamente: asignar responsable, transferencias, cerrar, cancelar) --}}
      <div class="border rounded p-3">
        <div class="font-semibold mb-2">Acciones</div>
        <div class="text-sm text-gray-600">En el próximo paso añadiremos: asignar responsable, solicitar transferencia, aceptar/rechazar, cerrar/cancelar canasta.</div>
      </div>
    </div>
  </div>
@endsection
