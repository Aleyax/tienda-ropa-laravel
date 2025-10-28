<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'name',
        'sku',
        'unit_price',
        'qty',
        'backorder_qty',
        'amount',

        // picking audit
        'picked_qty',
        'unpicked_qty',
        'picked_by',
        'picked_at',
    ];

    protected $casts = [
        'unit_price'  => 'float',
        'amount'      => 'float',
        'qty'         => 'int',
        'backorder_qty' => 'int',
        'picked_qty'  => 'int',
        'unpicked_qty' => 'int',
        'picked_at'   => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function pickedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    // Helpers picking
    public function addPicked(int $qty, ?User $by = null): void
    {
        $this->picked_qty += max(0, $qty);
        if ($by) {
            $this->picked_by = $by->id;
            $this->picked_at = now();
        }
        $this->save();
    }

    public function addUnpicked(int $qty): void
    {
        $this->unpicked_qty += max(0, $qty);
        $this->picked_qty   = max(0, $this->picked_qty - $qty);
        $this->save();
    }
}
