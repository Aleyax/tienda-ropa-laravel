<?php

namespace App\Services;

use App\Models\PaymentLog;
use Illuminate\Support\Facades\Auth;

class PaymentLogger
{
    public static function log(
        string $event,
        array $payload = [],
        ?int $orderId = null,
        ?int $orderPaymentId = null,
        array $meta = []
    ): PaymentLog {
        // payloads opcionales: old/new dentro de $payload
        $old = $payload['old'] ?? null;
        $new = $payload['new'] ?? null;

        // meta mínima: ip y actor
        $baseMeta = [
            'by' => Auth::id(),
            'ip' => request()?->ip(),
            'route' => request()?->route()?->getName(),
            'user_agent' => request()?->userAgent(),
        ];

        return PaymentLog::create([
            'order_payment_id' => $orderPaymentId,
            'order_id' => $orderId,
            'actor_id' => Auth::id(),
            'event' => $event,
            'old_payload' => $old,
            'new_payload' => $new,
            'meta' => array_merge($baseMeta, $meta),
        ]);
    }
}
