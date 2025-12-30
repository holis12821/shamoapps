<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /*
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'users_id',
        'cart_id',
        'address',
        'payment',
        'total_price',
        'shipping_price',
        'status'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'shipping_price' => 'decimal:2',
    ];

    protected $appends = [
        'grand_total',
        'formatted'
    ];

    /* ============================
     | Accessors
     |============================ */

    public function getGrandTotalAttribute(): float 
    {
        return (float) $this->total_price + (float) $this->shipping_price;
    }
     
    public function getFormattedAttribute(): array
    {
        return [
            'total_price' => $this->rupiah($this->total_price),
            'shipping_price' => $this->rupiah($this->shipping_price),
            'grand_total' => $this->rupiah($this->grand_total),
        ];
    }

     public function rupiah($amount): string
    {
        return 'Rp ' . number_format($amount ?? 0, 0, ',', '.');
    }

    /* ============================
     | Relationships
     |============================ */

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'transactions_id', 'id');
    }


    /* ============================
     | Helpers
     |============================ */
     public function isPaid(): bool
     {
         return $this->status === 'PAID';
     }
}
