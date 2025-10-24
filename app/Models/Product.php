<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name','slug','description','status','price_base'];


    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function media()
    {
        return $this->hasMany(Media::class);
    }
}
