<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
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
            throw new ApiException('Cart ID header missing', 400);
        }

        $cart = Cart::where('public_id', $cartId)->first();

        if (!$cart) {
            throw new ApiException('Cart not found', 404);
        }

        /**
         * Cart status validation
         */
        if (!$cart->isActive()) {
            throw new ApiException('Cart is not active', 403);
        }

        /**
         * Expiry validation
         */
        if ($cart->isExpired()) {
            throw new ApiException('Cart expired', 403);
        }

        /**
         * Attach cart to request lifecycle
         */
        $request->attributes->set('cart', $cart);

        return $next($request);
    }
}
