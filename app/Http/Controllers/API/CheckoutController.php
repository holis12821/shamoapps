<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Transaction;
use App\Services\Midtrans\MidtransPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function checkout(
        Request $request,
        MidtransPaymentService $paymentService
    ) {
        $request->validate([
            'address' => ['required', 'string'],
        ]);

        $user = $request->user();

        /** Cart from middleware */
        $cart = $request->attributes->get('cart');

        if (!$cart || $cart->status !== 'active') {
            return ResponseFormatter::error(
                null,
                'Cart not found or already checked out',
                404
            );
        }

        if ($cart->items->isEmpty()) {
            return ResponseFormatter::error(
                null,
                'Cart Empty',
                422
            );
        }

        return DB::transaction(function () use ($cart, $user, $request, $paymentService) {

            $transaction = Transaction::create([
                'users_id' => $user->id,
                'address' => $request->address,
                'total_price' => $cart->total_amount,
                'shipping_price' => 100,
                'status' => Transaction::STATUS_PENDING,
            ]);

            foreach ($cart->items as $item) {
                $transaction->items()->create([
                    'products_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ]);
            }
            
            // Load Transaction
            $transaction->load('items');

            // Create Midtrans Snap
            $payment = $paymentService->createSnapPayment($transaction);

            /** Invalidate cart */
            $cart->update(['status' => 'checked_out']);

            return ResponseFormatter::success(
                [
                    'transaction' => [
                        'order_number' => $transaction->order_number,
                        'items' => $transaction->items->map(function ($item) {
                            return [
                                'products_id'  => $item->products_id,
                                'product_name' => $item->product_name,
                                'price'        => (int) $item->price,
                                'quantity'     => (int) $item->quantity,
                            ];
                        }),
                        'total_price' => (int) $transaction->total_price,
                        'shipping_price' => (int) $transaction->shipping_price,
                        'grand_total' => (int) $transaction->grand_total,
                        'status' => $transaction->status,
                    ],
                    'payment' => [
                        'status' => $payment->status,
                        'payment_url' => $payment->payment_url,
                    ],
                ],
                'Checkout Success'
            );
        });
    }
}
