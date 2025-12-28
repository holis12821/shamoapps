<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'secret_key',
        'users_id',
        'status',
        'expires_at',
        'device_fingerprint',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /* ============================
     | Relationships
     |============================ */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(CartItem::class, 'cart_id', 'id');
    }

    /* ============================
     | Business Helpers
     |============================ */

     public function isExpired(): bool
     {
         return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
     }

     public function isActive(): bool
     {
         return $this->status === 'active' && !$this->isExpired();
     }

     public function isCheckedOut(): bool
     {
         return $this->status === 'checked_out';
     }

     public function isAbandoned(): bool
     {
         return $this->status === 'abandoned';
     }

     public function touchExpiry(int $hours = 24): void
     {
         $this->update([
             'expires_at' => now()->addHours($hours),
         ]);
     }

     public function totalAmount(): float
     {
         return $this->items->sum(fn ($item) => $item->price * $item->quantity);
     }
}
