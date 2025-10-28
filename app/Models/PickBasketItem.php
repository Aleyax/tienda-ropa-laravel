<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};

class PickBasketItem extends Model
{
    protected $fillable = [
        'pick_basket_id',
        'order_item_id',
        'variant_id',

        'requested_qty',   // lo que se pretende pickear
        'picked_qty',      // lo pickeado en la canasta
        'unpicked_qty',    // si devuelves o quitas
        'status',          // pending | partial | done | returned
    ];

    protected $casts = [
        'requested_qty' => 'int',
        'picked_qty'    => 'int',
        'unpicked_qty'  => 'int',
    ];

    // Relaciones
    public function basket(): BelongsTo
    {
        return $this->belongsTo(PickBasket::class, 'pick_basket_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // Helpers
    public function pick(int $qty): void
    {
        $this->picked_qty += max(0, $qty);
        $this->syncStatus();
    }

    public function unpick(int $qty): void
    {
        $this->unpicked_qty += max(0, $qty);
        $this->picked_qty   = max(0, $this->picked_qty - $qty);
        $this->syncStatus();
    }

    public function syncStatus(): void
    {
        if ($this->picked_qty <= 0) {
            $this->status = 'pending';
        } elseif ($this->picked_qty < $this->requested_qty) {
            $this->status = 'partial';
        } else {
            $this->status = 'done';
        }
        $this->save();
    }
}
