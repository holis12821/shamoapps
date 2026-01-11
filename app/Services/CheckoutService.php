<?php

namespace App\Services;

use App\Models\{
    Cart,
    Transaction,
    User
};
use App\Services\Midtrans\MidtransPaymentService;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected MidtransPaymentService $paymentService
    ) {}

    public function checkout(Cart $cart, User $user, string $address): array
    {
        return DB::transaction(function () use ($cart, $user, $address) {

            $items = $cart->items;

            $shippingPrice = 100; // Implement shipping calculation as needed
            $total = $cart->total_amount + $shippingPrice;

            $transaction = Transaction::create([
                'users_id' => $user->id,
                'address' => $address,
                'total_price' => $total,
                'shipping_price' => $shippingPrice,
                'status' => Transaction::STATUS_PENDING, // or PENDING if using payment
            ]);

            /** 2. Create Transaction Items */
            foreach ($items as $item) {
                $transaction->items()->create([
                    'products_id'  => $item->product_id,
                    'product_name' => $item->product_name,
                    'price'        => $item->price,
                    'quantity'     => $item->quantity,
                ]);
            }

            $transaction->load('items');

            /** 3. Create Midtrans Snap */
            $payment = $this->paymentService->createSnapPayment($transaction);

            /** 4. Invalidate Cart */
            $cart->update([
                'status' => 'checked_out',
                'secret_key' => '',
            ]);

            /** 5. Return API Payload */
            return $this->formatResponse($transaction, $payment);
        });
    }

    private function formatResponse(Transaction $transaction, $payment): array
    {
        return [
            'transaction' => [
                'order_number'   => $transaction->order_number,
                'items'          => $transaction->items->map(fn($item) => [
                    'products_id'  => $item->products_id,
                    'product_name' => $item->product_name,
                    'price'        => (int) $item->price,
                    'quantity'     => (int) $item->quantity,
                ]),
                'total_price'    => (int) $transaction->total_price,
                'shipping_price' => (int) $transaction->shipping_price,
                'grand_total'    => (int) $transaction->grand_total,
                'status'         => $transaction->status,
            ],
            'payment' => [
                'status'       => $payment->status,
                'payment_url'  => $payment->payment_url,
            ],
        ];
    }
}
