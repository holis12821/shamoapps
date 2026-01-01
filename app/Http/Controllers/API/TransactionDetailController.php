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
    public function show($id)
    {
        $transaction = Transaction::with(['items.product.galleries', 'user'])
            ->where('id', $id)
            ->where('users_id', Auth::id())
            ->first();

        if (!$transaction) {
            return ResponseFormatter::error(
                null,
                'Transaction not found',
                404
            );
        }

        return ResponseFormatter::success([
            'id' => $transaction->id,
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
                        'price' => $item->product->price,
                        'formatted' => $item->product->formatted,
                        'galleries' => $item->product->galleries->map(fn ($g) => [
                            'url' => $g->url,
                        ]),
                    ],
                ];
            }),

            'user' => [
                'id' => $transaction->user->id,
                'name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
        ], 'Transaction detail retrieved successfully');
    }
}
