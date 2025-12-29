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

        $cart->load('items');

        return ResponseFormatter::success(
            $cart,
            'Cart retrieved successfully'
        );
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

        $service->merge(
            $cart,
            $user
        );

        return ResponseFormatter::success(
            null,
            'Cart claimed successfully'
        );
    }
}
