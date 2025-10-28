<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'color_id', 'size_id', 'sku', 'barcode', 'stock', 'price_base'];
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
    public function color()
    {
        return $this->belongsTo(\App\Models\Color::class);
    }
    public function size()
    {
        return $this->belongsTo(\App\Models\Size::class);
    }
    public function warehouseStocks()
    {
        return $this->hasMany(\App\Models\WarehouseStock::class, 'variant_id');
    }

    /**
     * Total on hand sumando todos los almacenes (si usas multi-almacén).
     */
    public function total_on_hand(): int
    {
        return (int) $this->warehouseStocks()->sum('on_hand');
    }

    /**
     * Total disponible (on_hand - reserved) sumando todos los almacenes.
     * Útil cuando uses reservas (mayorista).
     */
    public function total_available(): int
    {
        // Nota: si aún no manejas reservas, esto ≈ total_on_hand
        return (int) $this->warehouseStocks()
            ->selectRaw('SUM(on_hand - reserved) as avail')
            ->value('avail') ?? 0;
    }
}
