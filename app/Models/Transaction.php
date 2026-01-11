<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';


    protected static function booted()
    {
        static::creating(function ($transaction) {
            $transaction->order_number = self::generateOrderNumber();
        });
    }

    /*
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'users_id',
        'order_number',
        'address',
        'total_price',
        'shipping_price',
        'status'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'shipping_price' => 'decimal:2',
        'status' => 'string',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
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

    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'transactions_id', 'id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'transactions_id', 'id');
    }


    /* ============================
     | Helpers
     |============================ */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
        ]);
    }

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');

        $lastOrder = Transaction::withTrashed()
            ->where('order_number', 'like', 'ORDER-%')
            ->latest('id')
            ->first();

        $sequence = $lastOrder
            ? ((int) substr($lastOrder->order_number, -6)) + 1
            : 100001;

        return sprintf('ORDER-%s-%06d', $date, $sequence);
    }
}
