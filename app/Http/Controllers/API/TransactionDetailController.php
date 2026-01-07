<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionDetailController extends Controller
{
    /**
     * Transaction Detail
     */
    public function show(string $orderNumber)
    {
        $transaction = Transaction::with([
                'items.product.galleries',
                'payment'
            ])
            ->where('users_id', Auth::id()) // User isolation
            ->where('order_number', $orderNumber)
            ->first();

        if (!$transaction) {
            return ResponseFormatter::error(
                null,
                'Transaction not found',
                404
            );
        }

        return ResponseFormatter::success(
            [
                'transaction' => [
                    'id' => $transaction->id,
                    'order_number' => $transaction->order_number,
                    'status' => $transaction->status,
                    'total_price' => $transaction->total_price,
                    'shipping_price' => $transaction->shipping_price,
                    'grand_total' => $transaction->grand_total,
                    'created_at' => $transaction->created_at,

                    'formatted' => $transaction->formatted,

                    'items' => $transaction->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'subtotal' => $item->subtotal,
                            'formatted' => $item->formatted,

                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'formatted' => $item->product->formatted,
                                'galleries' => $item->product->galleries->map(fn ($g) => [
                                    'url' => $g->url,
                                ]),
                            ],
                        ];
                    }),
                ],

                'payment' => $transaction->payment
                    ? [
                        'gateway' => 'midtrans',
                        'payment_type' => $transaction->payment->payment_type,
                        'status' => $transaction->payment->status,
                        'payment_url' => $transaction->payment->payment_url,
                    ]
                    : null,
            ],
            'Transaction detail retrieved successfully'
        );
    }
}
