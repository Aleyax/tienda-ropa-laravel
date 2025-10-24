<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $fillable = ['name', 'hex'];
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function media()
    {
        return $this->hasMany(Media::class);
    }
}
