<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = ['product_id', 'color_id', 'url', 'is_primary'];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
    public function color()
    {
        return $this->belongsTo(\App\Models\Color::class);
    }
}
