<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseFormatter;
use App\Models\Cart;
use Closure;

class ResolveCart
{
    public function handle($request, Closure $next)
    {
        /**
         * Resolve cart by public identifier
         * Header: X-CART-ID
         */
        $cartId = $request->header('X-CART-ID');

        if (!$cartId) {
            return ResponseFormatter::error(
                null,
                'Cart ID header missing',
                400
            );
        }

        $cart = Cart::where('public_id', $cartId)->first();

        if (!$cart) {
            return ResponseFormatter::error(
                null,
                'Cart not found',
                404
            );
        }

        /**
         * Cart status validation
         */
        if (!$cart->isActive()) {
            return ResponseFormatter::error(
                null,
                'Cart is not active',
                403
            );
        }

        /**
         * Expiry validation
         */
        if ($cart->isExpired()) {
            return ResponseFormatter::error(
                null,
                'Cart expired',
                403
            );
        }

        /**
         * Attach cart to request lifecycle
         */
        $request->attributes->set('cart', $cart);

        return $next($request);
    }
}