<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCartSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cart = $request->get('cart');
        $secret = $request->header('X-CART-SECRET');

        if (!$secret) {
            throw new ApiException('Cart secret header missing', 400);
        }

        if (!$cart || !$cart->secret_key) {
            throw new ApiException('Cart already finalized', 403);
        }

        if (!hash_equals($cart->secret_key, $secret)) {
            throw new ApiException('Invalid cart secret', 403);
        }


        return $next($request);
    }
}
