<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = ['user_id', 'name', 'contact_name', 'phone', 'country', 'region', 'province', 'district', 'line1', 'reference', 'is_default'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
