@props(['logs', 'filterEvent' => null, 'filterActor' => null, 'filterFrom' => null, 'filterTo' => null])

{{-- Filtros --}}
<form method="GET" class="grid md:grid-cols-5 gap-2 mb-3">
    <input type="hidden" name="tab" value="historial">
    <input name="log_event" value="{{ $filterEvent }}" class="border p-2" placeholder="Evento (texto)">
    <input name="log_actor" value="{{ $filterActor }}" class="border p-2" placeholder="Actor (user_id)">
    <input type="date" name="log_from" value="{{ $filterFrom }}" class="border p-2">
    <input type="date" name="log_to" value="{{ $filterTo }}" class="border p-2">
    <button class="bg-gray-800 text-white px-3 rounded">Filtrar</button>
</form>

@if ($logs->count() === 0)
    <div class="text-sm text-gray-600">Sin registros aún.</div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="p-2 border">#</th>
                    <th class="p-2 border">Fecha</th>
                    <th class="p-2 border">Evento</th>
                    <th class="p-2 border">Actor</th>
                    <th class="p-2 border">Pago</th>
                    <th class="p-2 border">Detalle</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr class="align-top">
                        <td class="p-2 border text-xs text-gray-500">{{ $log->id }}</td>
                        <td class="p-2 border text-xs text-gray-600">
                            {{ $log->created_at?->format('d/m/Y H:i') }}
                        </td>
                        <td class="p-2 border text-sm">
                            <span class="px-2 py-0.5 rounded bg-gray-100">{{ $log->event }}</span>
                        </td>
                        <td class="p-2 border text-sm">
                            @if ($log->user)
                                {{ $log->user->name }}
                                <span class="text-xs text-gray-500">({{ $log->user_id }})</span>
                            @else
                                <span class="text-xs text-gray-500">—</span>
                            @endif
                        </td>
                        <td class="p-2 border text-xs">
                            @if ($log->order_payment_id)
                                #{{ $log->order_payment_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-2 border text-xs">
                            @php $meta = $log->meta ?? []; @endphp
                            @if (!empty($meta))
                                <div class="text-gray-700">
                                    @if (isset($meta['reason']))
                                        <div><strong>Razón:</strong> {{ $meta['reason'] }}</div>
                                    @endif
                                    @if (isset($meta['note']) && $meta['note'])
                                        <div><strong>Nota:</strong> {{ $meta['note'] }}</div>
                                    @endif
                                    @if (isset($meta['ip']))
                                        <div class="text-gray-500">IP: {{ $meta['ip'] }}</div>
                                    @endif
                                    @if (isset($meta['route']))
                                        <div class="text-gray-500">Route: {{ $meta['route'] }}</div>
                                    @endif
                                </div>
                            @endif

                            <details class="mt-1">
                                <summary class="cursor-pointer text-blue-700">Payload</summary>
                                <div class="grid md:grid-cols-2 gap-2 mt-1">
                                    <div>
                                        <div class="text-gray-500 mb-1">Old</div>
                                        <pre class="text-[11px] bg-gray-50 p-2 rounded overflow-x-auto">@json($log->old_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                    </div>
                                    <div>
                                        <div class="text-gray-500 mb-1">New</div>
                                        <pre class="text-[11px] bg-gray-50 p-2 rounded overflow-x-auto">@json($log->new_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                    </div>
                                </div>
                            </details>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        {{ $logs->links() }}
    </div>
@endif
