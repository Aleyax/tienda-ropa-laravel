<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class PickBasket extends Model
{
    protected $fillable = [
        'order_id',
        'warehouse_id',
        'responsible_user_id',
        'status',            // open | picking | paused | closed | cancelled
        'created_by_user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PickBasketItem::class, 'pick_basket_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(PickBasketTransfer::class, 'pick_basket_id');
    }

    // Scopes rápidos
    public function scopeOpen($q)
    {
        return $q->where('status', 'open');
    }

    public function scopeActive($q)
    {
        return $q->whereIn('status', ['open', 'picking', 'paused']);
    }

    // Helpers
    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'picking', 'paused'], true);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['open', 'picking'], true);
    }

    public function markClosed(): void
    {
        $this->status = 'closed';
        $this->save();
    }

    public function assignTo(User $user): void
    {
        $this->responsible_user_id = $user->id;
        $this->save();
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }
}
