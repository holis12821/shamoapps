<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;

class CheckoutController extends Controller
{
    /**
     * Checkout cart becomes transaction
     */
    public function checkout(
    CheckoutRequest $request,
    CheckoutService $service
) {
    $transaction = $service->checkout(
        $request->get('cart'),
        $request->user(),
        $request->address
    );

    return ResponseFormatter::success(
        $transaction,
        'Checkout created successfully'
    );
}
}
