<?php

namespace App\Support;

final class Payments
{
    // Estados de OrderPayment que suman al saldo
    public const VALID_STATUSES_FOR_BALANCE = [
        'authorized', 'paid', 'partially_paid',
    ];

    // Estados globales posibles de Order.payment_status
    public const ORDER_PAYMENT_STATES = [
        'unpaid','pending_confirmation','cod_promised','authorized','paid','failed','partially_paid'
    ];

    // Pequeña tolerancia de centavos para comparación de doubles
    public const EPSILON = 0.009;
}
