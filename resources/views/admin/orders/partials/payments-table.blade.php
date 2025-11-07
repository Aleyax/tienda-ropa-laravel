@props(['rows' => collect(), 'showActions' => false, 'mode' => 'active'])

@if ($rows->isEmpty())
    <div class="text-sm text-gray-600">No hay registros.</div>
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
                @if ($showActions)
                    <th class="p-2 border text-center">Acciones</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $p)
                <tr class="{{ method_exists($p, 'trashed') && $p->trashed() ? 'opacity-60 bg-gray-50' : '' }}">
                    <td class="p-2 border text-center">{{ $p->id }}</td>
                    <td class="p-2 border">{{ $p->method }}</td>
                    <td class="p-2 border text-right">S/ {{ number_format($p->amount, 2) }}</td>
                    <td class="p-2 border text-xs text-gray-600">{{ $p->provider_ref ?? '—' }}</td>
                    <td class="p-2 border text-center">
                        @if ($p->evidence_url)
                            <button class="text-blue-600 underline"
                                @click="$dispatch('open-view-evidence', { url: '{{ $p->evidence_url }}' })">Ver</button>
                            @if (auth()->user()->hasAnyRole(['admin', 'vendedor']) && $mode === 'active')
                                <form method="POST" action="{{ route('admin.orders.payments.evidence.delete', $p) }}"
                                    class="inline">
                                    @csrf @method('DELETE')
                                    <button class="ml-2 text-xs underline text-red-700">Eliminar comprobante</button>
                                </form>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="p-2 border">
                        <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800">
                            {{ str_replace('_', ' ', $p->status) }}
                        </span>
                    </td>
                    <td class="p-2 border text-sm text-gray-600">
                        {{ $p->collectedBy?->name ?? '—' }}<br>
                        <span class="text-xs">{{ $p->collected_at?->format('d/m/Y H:i') ?? '' }}</span>
                    </td>

                    @if ($showActions)
                        <td class="p-2 border text-center space-y-1">
                            @if (
                                $mode === 'active' &&
                                    auth()->user()->hasAnyRole(['admin', 'vendedor']))
                                {{-- Actualizar estado --}}
                                <form method="POST" action="{{ route('admin.orders.payments.status', $p) }}">
                                    @csrf
                                    <select name="status" class="border p-1 text-sm">
                                        @foreach (['pending_confirmation', 'authorized', 'paid', 'failed', 'partially_paid', 'refunded'] as $st)
                                            <option value="{{ $st }}" @selected($p->status === $st)>
                                                {{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                                        @endforeach
                                    </select>
                                    <button
                                        class="ml-2 bg-gray-800 text-white px-2 py-1 rounded text-xs">Actualizar</button>
                                </form>

                                {{-- Confirmar paid rápido --}}
                                <form method="POST" action="{{ route('admin.orders.payments.status', $p) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="paid">
                                    <button class="bg-emerald-600 text-white px-2 py-1 rounded text-xs">Confirmar
                                        (paid)</button>
                                </form>

                                {{-- Eliminar (soft) si tienes SoftDeletes activado --}}
                                @if (Route::has('admin.orders.payments.destroy') && method_exists($p, 'trashed'))
                                    <form method="POST" action="{{ route('admin.orders.payments.destroy', $p) }}"
                                        onsubmit="return confirm('¿Eliminar este pago?');">
                                        @csrf @method('DELETE')
                                        <button
                                            class="bg-red-600 text-white px-2 py-1 rounded text-xs">Eliminar</button>
                                    </form>
                                @endif
                            @endif

                            @if ($mode === 'deleted' && method_exists($p, 'trashed') && $p->trashed())
                                {{-- Restaurar --}}
                                @if (Route::has('admin.orders.payments.restore'))
                                    <form method="POST" action="{{ route('admin.orders.payments.restore', $p) }}">
                                        @csrf
                                        <button
                                            class="bg-blue-600 text-white px-2 py-1 rounded text-xs">Restaurar</button>
                                    </form>
                                @endif
                                {{-- Eliminar definitivo (solo admin) --}}
                                @if (auth()->user()->hasRole('admin') && Route::has('admin.orders.payments.forceDelete'))
                                    <form method="POST" action="{{ route('admin.orders.payments.forceDelete', $p) }}"
                                        onsubmit="return confirm('¿Eliminar DEFINITIVAMENTE?');">
                                        @csrf @method('DELETE')
                                        <button class="bg-red-800 text-white px-2 py-1 rounded text-xs">Eliminar
                                            definitivo</button>
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
