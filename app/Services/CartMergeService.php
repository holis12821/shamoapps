<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartMergeService
{
    /**
     * Claim / merge anonymous cart into user cart
     *
     * Flow:
     * 1. User logs in
     * 2. Client sends X-CART-ID + X-CART-SECRET
     * 3. Middleware validates cart & secret
     * 4. This service is called
     */

    public function merge(Cart $incomingCart, User $user): void
    {
        // Merge items from incoming cart to user cart
        DB::transaction(function () use ($incomingCart, $user) {

            /**
             * Case 1 Get user's active cart (excluding incoming cart)
             */
            $userCart = Cart::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where('id', '!=', $incomingCart->id)
                ->first();

            /**
             * 2. CASE: User has NO active cart
             * → claim incoming cart directly
             */

            if (!$userCart) {
                // No existing user cart, just assign ownership
                $incomingCart->update([
                    'user_id' => $user->id,
                ]);

                return;
            }

            /**
             * 3. CASE: Incoming cart already belongs to user
             * → nothing to do
             */
            if ($incomingCart->user_id === $user->id) {
                return;
            }

            /**
             * CASE 4:
             * User has an active cart → merge items
             */

            foreach ($incomingCart->items as $item) {
                $userCart->items()->updateOrCreate(
                    [
                        'product_id' => $item->product_id,
                    ],
                    [
                        // snapshots are retained
                        'product_name' => $item->product_name,
                        'price' => $item->price,

                        'quantity' => DB::raw('quantity + ' . (int) $item->quantity),
                    ]
                );
            }

            /**
             * 5. Delete incoming cart (anonymous)
             */
            $incomingCart->items()->delete();
            $incomingCart->delete();
        });
    }
}
