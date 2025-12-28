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
    public function create(CartService $service)
    {
        $payload = $service->create();

        return ResponseFormatter::success($payload, 'Cart created successfully');
    }

    public function show(Request $request)
    {
        $cart = $request->get('cart')->load('items');

        return ResponseFormatter::success($cart, 'Cart retrieved successfully');
    }

    public function addItem(
        AddCartItemRequest $request,
        CartItemService $service
    ) {
        $service->add(
            $request->get('cart'),
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
        $service->update(
            $request->get('cart'),
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
        $service->remove(
            $request->get('cart'),
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
        if (!$request->user()) {
            return ResponseFormatter::error(
                null,
                'Unauthorized',
                401
            );
        }

        $service->merge(
            $request->get('cart'),
            $request->user()
        );

        return ResponseFormatter::success(
            null,
            'Cart claimed successfully'
        );
    }
}
