<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
        protected $fillable = ['group_id','name','currency','is_active'];

    public function group()
    {
        return $this->belongsTo(\App\Models\CustomerGroup::class, 'group_id');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\PriceListItem::class, 'price_list_id');
    }
}
