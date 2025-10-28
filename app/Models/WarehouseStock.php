<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStock extends Model
{
    protected $fillable = [
        'warehouse_id',
        'variant_id',
        'on_hand',
        'reserved',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // Helpers útiles:
    public function available(): int
    {
        // Disponible para pick si usas reservas
        return max(0, (int)$this->on_hand - (int)$this->reserved);
    }
}
