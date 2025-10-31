<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    // Opcional: constantes de estado
    public const STATUS_PENDING_CONFIRMATION = 'pending_confirmation';
    public const STATUS_AUTHORIZED          = 'authorized';
    public const STATUS_PAID                = 'paid';
    public const STATUS_FAILED              = 'failed';
    public const STATUS_REFUNDED            = 'refunded';
    public const STATUS_PARTIALLY_PAID      = 'partially_paid';

    protected $fillable = [
        'order_id',
        'method',        // transfer | cod | online | etc.
        'amount',
        'status',        // ver constantes arriba (opcional)
        'provider_ref',  // referencia del banco/pasarela
        'evidence_url',  // link al comprobante (imagen/pdf)
        'collected_by',  // user_id quien registró
        'collected_at',  // cuándo se registró
    ];

    protected $casts = [
        'amount'       => 'decimal:2',  // mejor que float
        'collected_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ✅ usar la FK real que tienes en DB: collected_by
    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
