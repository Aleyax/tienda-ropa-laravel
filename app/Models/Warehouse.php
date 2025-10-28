<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = ['name','code','is_active','address'];

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }
}
