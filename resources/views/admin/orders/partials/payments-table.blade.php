@props([
  'rows' => collect(),
  'showActions' => false,
  'mode' => 'active', // 'active' | 'deleted'
])

@if ($rows->isEmpty())
  <div class="text-sm text-gray-600">No hay registros para mostrar.</div>
@else
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
        @if($showActions)
          <th class="p-2 border">Acciones</th>
        @endif
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $row)
        <tr class="{{ method_exists($row, 'trashed') && $row->trashed() ? 'opacity-60 bg-gray-50' : '' }}">
          <td class="p-2 border text-center">{{ $row->id }}</td>
          <td class="p-2 border">{{ $row->method }}</td>
          <td class="p-2 border text-right">S/ {{ number_format($row->amount, 2) }}</td>
          <td class="p-2 border text-xs text-gray-600">{{ $row->provider_ref ?? '—' }}</td>
          <td class="p-2 border text-center">
            @if ($row->evidence_url)
              <button
                type="button"
                class="text-blue-600 underline"
                @click="$dispatch('open-view-evidence', { url: '{{ $row->evidence_url }}' })">
                Ver
              </button>
              @if ($mode === 'active' && auth()->user()->hasAnyRole(['admin','vendedor']))
                <form method="POST"
                      action="{{ route('admin.orders.payments.evidence.delete', $row) }}"
                      class="inline">
                  @csrf
                  @method('DELETE')
                  <button class="ml-2 text-xs underline text-red-700"
                          onclick="return confirm('¿Eliminar comprobante?')">
                    Eliminar comprobante
                  </button>
                </form>
              @endif
            @else
              —
            @endif
          </td>
          <td class="p-2 border">
            <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800">
              {{ str_replace('_', ' ', $row->status) }}
            </span>
            @if (method_exists($row, 'trashed') && $row->trashed())
              <span class="ml-1 text-xs px-2 py-0.5 rounded bg-red-100 text-red-800">
                Eliminado
              </span>
            @endif
          </td>
          <td class="p-2 border text-sm text-gray-600">
            {{ $row->collectedBy?->name ?? '—' }}<br>
            <span class="text-xs">{{ $row->collected_at?->format('d/m/Y H:i') ?? '' }}</span>
          </td>

          @if($showActions)
            <td class="p-2 border text-center space-y-1">
              @if (!method_exists($row, 'trashed') || !$row->trashed())
                {{-- Cambiar estado --}}
                <form method="POST" action="{{ route('admin.orders.payments.status', $row) }}" class="inline-flex items-center gap-1">
                  @csrf
                  <select name="status" class="border p-1 text-xs">
                    @foreach (['pending_confirmation','authorized','paid','failed','partially_paid','refunded'] as $st)
                      <option value="{{ $st }}" @selected($row->status === $st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                    @endforeach
                  </select>
                  <button class="bg-gray-800 text-white px-2 py-1 rounded text-xs">Actualizar</button>
                </form>

                {{-- Confirmar paid directo --}}
                <form method="POST" action="{{ route('admin.orders.payments.status', $row) }}" class="inline">
                  @csrf
                  <input type="hidden" name="status" value="paid">
                  <button class="bg-emerald-600 text-white px-2 py-1 rounded text-xs">Confirmar (paid)</button>
                </form>

                {{-- Eliminar (soft) --}}
                <form method="POST"
                      action="{{ route('admin.orders.payments.destroy', $row) }}"
                      class="inline"
                      onsubmit="return confirm('¿Eliminar este pago? (Se puede restaurar)')">
                  @csrf @method('DELETE')
                  <button class="bg-gray-200 text-gray-800 px-2 py-1 rounded text-xs">Eliminar</button>
                </form>
              @else
                {{-- Restaurar --}}
                <form method="POST" action="{{ route('admin.orders.payments.restore', $row->id) }}" class="inline">
                  @csrf
                  <button class="bg-blue-600 text-white px-2 py-1 rounded text-xs">Restaurar</button>
                </form>

                {{-- Eliminar definitivo (solo admin) --}}
                @if (auth()->user()?->hasRole('admin'))
                  <form method="POST"
                        action="{{ route('admin.orders.payments.forceDelete', $row->id) }}"
                        class="inline"
                        onsubmit="return confirm('¿Eliminar DEFINITIVAMENTE?')">
                    @csrf @method('DELETE')
                    <button class="bg-red-700 text-white px-2 py-1 rounded text-xs">Eliminar definitivo</button>
                  </form>
                @endif
              @endif
            </td>
          @endif
        </tr>
      @endforeach
    </tbody>
  </table>
@endif
