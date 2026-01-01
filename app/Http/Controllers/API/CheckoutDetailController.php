<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CheckoutDetailController extends Controller
{
    /**
     * Preview checkout detail (GET)
     * Does NOT create transaction
     */
    public function preview(Request $request)
    {
        $cart = $request->get('cart');

        if (!$cart) {
            return ResponseFormatter::error(
                null,
                'Cart not found',
                404
            );
        }

        $cart->load([
            'items.product.galleries',
        ]);

        return ResponseFormatter::success([
            'cart' => [
                'id' => $cart->id,
                'status' => $cart->status,
                'total_quantity' => $cart->total_quantity,
                'total_amount' => $cart->total_amount,
                'formatted' => $cart->formatted,
            ],
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                    'formatted' => $item->formatted,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'price' => $item->product->price,
                        'formatted' => $item->product->formatted,
                        'galleries' => $item->product->galleries->map(fn($g) => [
                            'url' => $g->url,
                        ]),
                    ],
                ];
            }),
        ], 'Checkout preview retrieved successfully');
    }
}
