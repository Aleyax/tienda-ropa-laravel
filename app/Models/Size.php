<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $fillable = ['code', 'name', 'region'];
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
