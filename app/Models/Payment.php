<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        // FK
        'transactions_id',

        // Midtrans metadata
        'midtrans_transaction_id',
        'payment_type',
        'payment_url',

        // Payment state
        'status',

        // Audit
        'transaction_time',
        'settlement_time',
        'fraud_status',

        // Callback payload
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'transaction_time' => 'datetime',
        'settlement_time' => 'datetime',
    ];

    // Relation to Transaction
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transactions_id');
    }
}
