<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};

class PickBasketTransfer extends Model
{
    protected $fillable = [
        'pick_basket_id',
        'from_user_id',
        'to_user_id',
        'status',      // pending | accepted | declined | cancelled | expired
        'note',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    // Relaciones
    public function pickBasket(): BelongsTo
    {
        return $this->belongsTo(PickBasket::class, 'pick_basket_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    // Helpers
    public function accept(): void
    {
        $this->status = 'accepted';
        $this->decided_at = now();
        $this->save();
    }

    public function decline(): void
    {
        $this->status = 'declined';
        $this->decided_at = now();
        $this->save();
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->decided_at = now();
        $this->save();
    }
}
