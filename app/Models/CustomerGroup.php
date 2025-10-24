<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    //
    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(User::class, 'group_id');
    }
    public function priceLists()
    {
        return $this->hasMany(\App\Models\PriceList::class, 'group_id');
    }

    public function activePriceList()
    {
        return $this->hasOne(\App\Models\PriceList::class, 'group_id')->where('is_active', true);
    }
}
