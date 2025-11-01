<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderLog;

class OrderLogger
{
    public static function log(Order $order, string $action, $old = null, $new = null, array $meta = []): void
    {
        OrderLog::create([
            'order_id'  => $order->id,
            'user_id'   => auth()->id(),
            'action'    => $action,
            'old_value' => is_scalar($old) ? (string)$old : ( $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null ),
            'new_value' => is_scalar($new) ? (string)$new : ( $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null ),
            'meta'      => $meta ?: null,
        ]);
    }
}
