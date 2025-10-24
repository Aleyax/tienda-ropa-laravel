@extends('admin.layout')

@section('title',"Pedido #{$order->id}")

@section('content')
  <div class="grid md:grid-cols-3 gap-4">
    <div class="md:col-span-2 space-y-4">
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
                <td class="p-2 border">{{ $variant->product->name }}</td>
                <td class="p-2 border">{{ $variant->color->name }} / {{ $variant->size->code }}</td>
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
    </div>
  </div>
@endsection
