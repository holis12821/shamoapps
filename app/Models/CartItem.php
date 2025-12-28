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
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

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
