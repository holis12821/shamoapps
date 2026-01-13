<?php

namespace App\Services;

use App\Exceptions\Cart\CartAlreadyCheckedOutException;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartMergeService
{
    /**
     * Idempotent cart claim / merge
     */
    public function claim(Cart $incomingCart, User $user): Cart
    {
        return DB::transaction(function () use ($incomingCart, $user) {

            $incomingCart = Cart::query()
                ->whereKey($incomingCart->id)
                ->lockForUpdate()
                ->firstOrFail();

            /**
             * 1. TERMINAL STATE → STOP
             */
            if ($incomingCart->status === 'checkout_out') {
                throw new CartAlreadyCheckedOutException();
            }

            /**
             * 2. EXPIRED → May be reset
             */
            if ($incomingCart->status === 'expired') {
                $incomingCart->update(['status' => 'active']);
                $incomingCart->refresh();
            }

            /**
             * 3. IDEMPOTENT CHECK
             */
            if ($incomingCart->user_id === $user->id) {
                return $incomingCart;
            }

            // User active cart
            $userCart = Cart::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where('id', '!=', $incomingCart->id)
                ->lockForUpdate()
                ->first();

            // No existing cart → claim directly
            if (! $userCart) {
                $incomingCart->update([
                    'user_id' => $user->id,
                ]);

                return $incomingCart->fresh();
            }

            // Merge items
            foreach ($incomingCart->items as $item) {
                $existingItem = $userCart->items()
                    ->where('product_id', $item->product_id)
                    ->first();

                if ($existingItem) {
                    $existingItem->increment('quantity', $item->quantity);
                } else {
                    $userCart->items()->create([
                        'product_id'   => $item->product_id,
                        'product_name' => $item->product_name,
                        'price'        => $item->price,
                        'quantity'     => $item->quantity,
                    ]);
                }
            }

            // Cleanup guest cart
            $incomingCart->items()->delete();
            $incomingCart->delete();

            return $userCart->fresh();
        });
    }
}
