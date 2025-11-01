{{-- === Resumen de pagos === --}}
@php
    $global = $order->payment_status;
    $badge = function ($txt, $classes) {
        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs ' .
            $classes .
            '">' .
            $txt .
            '</span>';
    };
@endphp

<div class="border rounded p-3 bg-white">
    <div class="flex items-center justify-between mb-2">
        <div class="font-semibold">Resumen de pagos</div>
        <div class="text-xs">
            @if ($global === 'paid')
                {!! $badge('Pagado', 'bg-green-100 text-green-800') !!}
            @elseif($global === 'partially_paid')
                {!! $badge('Pago parcial', 'bg-amber-100 text-amber-800') !!}
            @elseif($global === 'pending_confirmation')
                {!! $badge('Pendiente conf.', 'bg-yellow-100 text-yellow-800') !!}
            @elseif($global === 'failed')
                {!! $badge('Fallido', 'bg-red-100 text-red-800') !!}
            @else
                {!! $badge(ucfirst(str_replace('_', ' ', $global)), 'bg-gray-100 text-gray-800') !!}
            @endif
        </div>
    </div>

    <div class="space-y-1 text-sm">
        <div class="flex justify-between"><span>Total pedido:</span> <strong>S/
                {{ number_format($orderTotal, 2) }}</strong></div>
        <div class="flex justify-between"><span>Pagado (confirmado):</span> <strong class="text-green-700">S/
                {{ number_format($sumPaid, 2) }}</strong></div>
        @if ($sumPending > 0)
            <div class="flex justify-between"><span>Pagos en revisión:</span> <strong class="text-amber-700">S/
                    {{ number_format($sumPending, 2) }}</strong></div>
        @endif
        @if ($sumFailed > 0)
            <div class="flex justify-between"><span>Fallidos / reembolsados:</span> <span class="text-red-700">S/
                    {{ number_format($sumFailed, 2) }}</span></div>
        @endif
        <div class="flex justify-between"><span>Pendiente por cobrar:</span>
            <strong class="{{ $remaining > 0 ? 'text-orange-700' : 'text-green-700' }}">S/
                {{ number_format($remaining, 2) }}</strong>
        </div>
    </div>

    {{-- Barra de progreso del pago --}}
    <div class="mt-3">
        <div class="h-2 w-full bg-gray-200 rounded">
            <div class="h-2 rounded {{ $progressPct >= 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                style="width: {{ $progressPct }}%;"></div>
        </div>
        <div class="mt-1 text-xs text-gray-600">{{ $progressPct }}% del pedido pagado</div>
    </div>

    {{-- Mensajes cortos --}}
    @if ($remaining <= 0 && $orderTotal > 0)
        <div class="mt-3 text-xs text-green-700 bg-green-50 border border-green-200 rounded p-2">
            ✔ Pago completo. Puedes continuar con el flujo logístico.
        </div>
    @elseif($sumPending > 0)
        <div class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
            ⏳ Hay pagos pendientes de confirmación. Al confirmarlos, el estado global se actualizará automáticamente.
        </div>
    @endif
</div>
