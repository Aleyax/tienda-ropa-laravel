<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    protected $guard_name = 'web';
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function group()
    {
        return $this->belongsTo(CustomerGroup::class, 'group_id');
    }
    public function addresses()
    {
        return $this->hasMany(\App\Models\Address::class);
    }
    public function isWholesale(): bool
    {
        return in_array($this->group?->name, ['mayorista', 'especial']);
    }
    public function isRetail(): bool
    {
        return optional($this->group)->name === 'minorista';
    }
    public function pickBasketsResponsible()
    {
        return $this->hasMany(\App\Models\PickBasket::class, 'responsible_user_id');
    }

    public function pickBasketsCreated()
    {
        return $this->hasMany(\App\Models\PickBasket::class, 'created_by_user_id');
    }

    public function outgoingTransfers()
    {
        return $this->hasMany(\App\Models\PickBasketTransfer::class, 'from_user_id');
    }

    public function incomingTransfers()
    {
        return $this->hasMany(\App\Models\PickBasketTransfer::class, 'to_user_id');
    }
    // app/Models/Warehouse.php
    public function pickBaskets()
    {
        return $this->hasMany(\App\Models\PickBasket::class, 'warehouse_id');
    }
    public function orderLogs()
    {
        return $this->hasMany(\App\Models\OrderLog::class);
    }

}
