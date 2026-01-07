<?php

namespace App\Services\Midtrans;

use App\Models\Payment;
use App\Models\Transaction;
use Midtrans\Snap;

class MidtransPaymentService
{
    /**
     * Create Midtrans Snap payment
     */
    public function createSnapPayment(Transaction $transaction): Payment
    {
        $orderId = $transaction->order_number;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $transaction->grand_total,
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
            'item_details' => $transaction->items->map(function ($item) {
                return [
                    'id'       => $item->product->id,
                    'price'    => (int) $item->price,
                    'quantity' => (int) $item->quantity,
                    'name'     => $item->product->name,
                ];
            })->toArray(),  
        ];

        $snap = Snap::createTransaction($params);

        return Payment::create([
            'transactions_id'        => $transaction->id,
            'midtrans_transaction_id' => null,
            'payment_type'           => 'snap',
            'payment_url'            => $snap->redirect_url,
            'status'                 => 'pending',
            'payload'                => (array) $snap,
        ]);
    }
}