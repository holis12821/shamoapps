<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\{
    Cart,
    Transaction,
    TransactionItem,
    User
};
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function checkout(Cart $cart, User $user, string $address): Transaction
    {
        if ($cart->status !== 'active') {
            throw new ApiException(
                'Cart cannot be checked out',
                409
            );
        }

        if ($cart->items()->count() === 0) {
            throw new ApiException(
                'Cart is empty',
                422
            );
        }

        return DB::transaction(function () use ($cart, $user, $address) {

            $items = $cart->items;

            $subtotal = $items->sum(
                fn ($item) => $item->price * $item->quantity
            );

            $shippingPrice = 100; // Implement shipping calculation as needed
            $total = $subtotal + $shippingPrice;

            $transaction = Transaction::create([
                'users_id' => $user->id,
                'cart_id' => $cart->id,
                'address' => $address,
                'total_price' => $total,
                'shipping_price' => $shippingPrice,
                'status' => 'PENDING', // or PENDING if using payment
            ]);

            foreach ($items as $item) {
                TransactionItem::create([
                    'transactions_id' => $transaction->id,
                    'users_id' => $user->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ]);
            }

            $cart->update([
                'status' => 'checked_out',
                'secret_key' => null,
            ]);

            return $transaction;
        });
    }
}
