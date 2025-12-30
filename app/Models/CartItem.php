<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    protected $appends = [
        'subtotal',
        'formatted'
    ];

    /* ============================
     | Accessors
     |============================ */

    public function getSubtotalAttribute(): float
    {
        return (float) $this->price * $this->quantity;
    }

    public function getFormattedAttribute(): array
    {
        return [
            'price' => $this->rupiah($this->price),
            'subtotal' => $this->rupiah($this->subtotal),
        ];
    }

    private function rupiah($amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }


    /* ============================
     | Relationships
     |============================ */

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id', 'id');
    }

    /**
     * Optional:
     * If you want a relationship to Product
     * (not used to calculate prices!)
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /* ============================
     | Helpers
     |============================ */

     public function subtotal(): float
     {
         return $this->price * $this->quantity;
     }
}
