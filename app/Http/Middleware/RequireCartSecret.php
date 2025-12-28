<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseFormatter;
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

        if (
            !$secret ||
            !hash_equals($cart->secret_key, $secret)
        ) {
            return ResponseFormatter::error(
                null,
                'Invalid cart secret',
                403
            );
        }

        return $next($request);
    }
}
