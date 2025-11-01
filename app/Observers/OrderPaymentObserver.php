<?php

namespace App\Observers;

use App\Models\OrderPayment;
use App\Services\OrderPaymentRecalculator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class OrderPaymentObserver
{
    protected OrderPaymentRecalculator $recalc;

    public function __construct()
    {
        $this->recalc = App::make(OrderPaymentRecalculator::class);
    }

    public function created(OrderPayment $payment): void
    {
        $this->recalcOrder($payment);
    }

    public function updated(OrderPayment $payment): void
    {
        $this->recalcOrder($payment);
    }

    public function deleted(OrderPayment $payment): void
    {
        // Ojo: tras delete, $payment->order sigue resolviendo (relación en memoria)
        $this->recalcOrder($payment);
    }

    private function recalcOrder(OrderPayment $p): void
    {
        $orderId = $p->order_id;
        if (!$orderId) return;

        // Lock y recálculo seguros
        $this->recalc->recalcSafe($orderId);
    }
}
