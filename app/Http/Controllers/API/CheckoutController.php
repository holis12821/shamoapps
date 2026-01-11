<?php

namespace App\Http\Controllers\API;

use App\Exceptions\CartEmptyException;
use App\Exceptions\CartNotActiveException;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function checkout(
        CheckoutRequest $request,
        CheckoutService $checkoutService
    ) {

        $user = $request->user();

        /** Cart from middleware */
        $cart = $request->attributes->get('cart');

        $this->validateCart($cart);

        /**
         * All business logic is in CheckoutService
         * Controller does not know transaction/payment details
         */

        $payload = $checkoutService->checkout(
            cart: $cart,
            user: $user,
            address: $request->address
        );

        return ResponseFormatter::success(
            $payload,
            'Checkout Success'
        );
    }

    private function validateCart($cart): void
    {
        if (!$cart || $cart->status !== 'active') {
            throw new CartNotActiveException();
        }

        if ($cart->items->isEmpty()) {
            throw new CartEmptyException();
        }
    }
}
