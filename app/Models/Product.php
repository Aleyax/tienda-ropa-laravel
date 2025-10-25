<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'status', 'price_base'];


    public function variants()
    {
        return $this->hasMany(\App\Models\ProductVariant::class);
    }
    public function media()
    {
        return $this->hasMany(\App\Models\Media::class);
    }
}
