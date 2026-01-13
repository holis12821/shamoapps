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
        $cart = $request->attributes->get('cart');
        $secret = $request->header('X-CART-SECRET');

        if (! $cart || ! $secret) {
            throw new ApiException('Unauthorized cart access', 401);
        }

        if (! hash_equals(
            (string) $cart->secret_key,
            (string) $secret
        )) {
            throw new ApiException('Unauthorized cart access', 401);
        }

        return $next($request);
    }
}
