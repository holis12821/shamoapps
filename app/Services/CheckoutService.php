<?php

namespace App\Services;

use App\Exceptions\CartEmptyException;
use App\Models\{
    Cart,
    Transaction,
    User
};
use App\Services\CartMergeService;
use App\Services\Midtrans\Payment\MidtransPaymentService;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected CartMergeService $cartMergeService,
        protected MidtransPaymentService $paymentService
    ) {}

    /**
     * FINAL Production-grade Checkout Flow
     *
     * Guarantees:
     * - Idempotent (by order_number)
     * - Safe for retry / double submit (mobile)
     * - Safe for Midtrans Snap & callback
     */
    public function checkout(Cart $cart, User $user, string $address): array
    {
        return DB::transaction(function () use ($cart, $user, $address) {

            /**
             * 1️⃣ CLAIM CART (idempotent & safe)
             * Guest → User
             */
            $cart = $this->cartMergeService->claim($cart, $user);
            $cart->refresh();

            /**
             * 2️⃣ VALIDATE CART
             */
            $this->validateCart($cart);

            /**
             * 3️⃣ RESOLVE ORDER NUMBER (IDEMPOTENCY KEY)
             * One checkout intent = one order_number
             */
            $orderNumber = $cart->order_number;

            if (! $orderNumber) {
                $orderNumber = Transaction::generateOrderNumber();

                $cart->update([
                    'order_number' => $orderNumber,
                ]);
            }

            /**
             * 4️⃣ IDEMPOTENCY CHECK (BY ORDER NUMBER)
             * Retry-safe / double submit protection
             */
            $existingTransaction = Transaction::where('order_number', $orderNumber)
                ->where('status', '!=', Transaction::STATUS_CANCELLED)
                ->first();

            if ($existingTransaction) {
                return $this->formatResponse(
                    $existingTransaction->load('items'),
                    $existingTransaction->payment
                );
            }

            /**
             * 5️⃣ CREATE TRANSACTION
             */
            $shippingPrice = 100; // TODO: move to ShippingService
            $totalPrice    = $cart->total_amount + $shippingPrice;

            $transaction = Transaction::create([
                'users_id'        => $user->id,
                'order_number'    => $orderNumber,
                'address'         => $address,
                'total_price'     => $totalPrice,
                'shipping_price'  => $shippingPrice,
                'status'          => Transaction::STATUS_PENDING,
            ]);

            /**
             * 6️⃣ COPY CART ITEMS → TRANSACTION ITEMS
             */
            foreach ($cart->items as $item) {
                $transaction->items()->create([
                    'products_id'  => $item->product_id,
                    'product_name' => $item->product_name,
                    'price'        => $item->price,
                    'quantity'     => $item->quantity,
                ]);
            }

            $transaction->load('items');

            /**
             * 7️⃣ CREATE MIDTRANS PAYMENT (SAFE — ONLY ONCE)
             */
            $payment = $transaction->payment
                ?? $this->paymentService->createSnapPayment($transaction);

            /**
             * 8️⃣ LOCK CART (FINAL STATE)
             */
            $cart->update([
                'status'     => Cart::STATUS_CHECKED_OUT,
                'user_id'    => $user->id,
                'secret_key' => '',
            ]);

            /**
             * 9️⃣ RETURN API RESPONSE
             */
            return $this->formatResponse($transaction, $payment);
        });
    }

    /**
     * Cart validation (business rule)
     */
    private function validateCart(Cart $cart): void
    {
        if ($cart->items->isEmpty()) {
            throw new CartEmptyException('Cart is empty.');
        }
    }

    /**
     * Unified Checkout API Response
     */
    private function formatResponse(Transaction $transaction, $payment): array
    {
        return [
            'transaction' => [
                'order_number'   => $transaction->order_number,
                'items'          => $transaction->items->map(fn($item) => [
                    'products_id'  => $item->products_id,
                    'product_name' => $item->product_name,
                    'price'        => $item->price,
                    'quantity'     => $item->quantity,
                    'subtotal'     => $item->subtotal,
                    'formatted'    => $item->formatted,
                ]),
                'total_price'    => $transaction->total_price,
                'shipping_price' => $transaction->shipping_price,
                'grand_total'    => $transaction->grand_total,
                'status'         => $transaction->status,
                'formatted'      => $transaction->formatted,
            ],
            'payment' => [
                'status'       => $payment->status,
                'payment_url'  => $payment->payment_url,
            ],
        ];
    }
}
