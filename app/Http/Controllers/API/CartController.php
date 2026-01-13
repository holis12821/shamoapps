<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Services\{
    CartService,
    CartItemService,
    CartMergeService,
};
use App\Models\CartItem;


class CartController extends Controller
{
    public function create(Request $request, CartService $service)
    {
        // user can be null for guest cart
        $user = $request->user();

        $payload = $service->create($user);

        return ResponseFormatter::success(
            $payload,
            'Cart created successfully'
        );
    }

    public function show(Request $request)
    {
        $cart = $request->get('cart');

        if (!$cart) {
            return ResponseFormatter::error(
                null,
                'Cart not found',
                404
            );
        }

        // Load related items and products with galleries
        $cart->load([
            'items.product.galleries'
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
                    'subtotal' => $item->subtotal,
                    'formatted' => $item->formatted,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'price' => $item->product->price,
                        'formatted' => $item->product->formatted,
                        'galleries' => $item->product->galleries->map(function ($gallery) {
                            return [
                                'url' => $gallery->url,
                            ];
                        }),
                    ],
                ];
            }),
        ], 'Cart retrieved successfully');
    }

    public function addItem(
        AddCartItemRequest $request,
        CartItemService $service
    ) {
        $cart = $request->get('cart');

        if (!$cart) {
            return ResponseFormatter::error(
                null,
                'Cart not found',
                404
            );
        }

        $service->add(
            $cart,
            $request->validated()
        );

        return ResponseFormatter::success(
            null,
            'Add item to cart successfully'
        );
    }

    public function updateItem(
        UpdateCartItemRequest $request,
        CartItem $item,
        CartItemService $service,
    ) {
        $cart = $request->get('cart');

        if (!$cart) {
            return ResponseFormatter::error(
                null,
                'Cart not found',
                404
            );
        }

        $service->update(
            $cart,
            $item,
            $request->validated()['quantity']
        );

        return ResponseFormatter::success(
            null,
            'Cart item updated successfully'
        );
    }

    public function removeItem(
        Request $request,
        CartItem $item,
        CartItemService $service,
    ) {
        $cart = $request->get('cart');

        if (!$cart) {
            return ResponseFormatter::error(
                null,
                'Cart not found',
                404
            );
        }

        $service->remove(
            $cart,
            $item
        );

        return ResponseFormatter::success(
            null,
            'Cart item removed successfully'
        );
    }

    public function claim(
        Request $request,
        CartMergeService $service
    ) {
        $user = $request->user();
        $cart = $request->get('cart');

        if (!$user) {
            return ResponseFormatter::error(
                null,
                'Unauthorized',
                401
            );
        }

        if (!$cart) {
            return ResponseFormatter::error(
                null,
                'Cart not found',
                404
            );
        }

        $service->claim(
            $cart,
            $user
        );

        return ResponseFormatter::success(
            null,
            'Cart claimed successfully'
        );
    }
}
