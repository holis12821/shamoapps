<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    /*
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'products_id',
        'transactions_id',
        'product_name',
        'price',
        'quantity'
    ];

    protected $casts = [
        'price' => 'decimal:2',
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

    /**
     * Optional reference to Product.
     * TransactionItem is a SNAPSHOT, so business logic
     * must rely on product_name & price, not this relation.
     */

    public function product()
    {
        return $this->belongsTo(Product::class, 'products_id')
        ->withDefault([
            'name' => $this->product_name,
            'price' => $this->price,
        ]);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transactions_id');
    }
}
