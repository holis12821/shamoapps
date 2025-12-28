<?php

namespace App\Services;

use App\Models\{
    Cart,
    CartItem,
    Product
};
use Illuminate\Support\Facades\DB;
use App\Exceptions\ApiException;

class CartItemService
{
    /**
     * Add item to cart
     * - If product already exists → increment quantity
     * - Else → create new cart item
     */
    public function add(Cart $cart, array $data): void
    {
        $productId = $data['product_id'] ?? null;
        $quantity  = $data['quantity'] ?? null;

        if (!$productId || !$quantity || $quantity < 1) {
            throw new ApiException('Invalid payload', 422);
        }

        $product = Product::findOrFail($productId);

        DB::transaction(function () use ($cart, $product, $quantity) {

            $item = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if ($item) {
                $item->increment('quantity', $quantity);
                return;
            }

            CartItem::create([
                'cart_id'      => $cart->id,
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'price'        => $product->price,
                'quantity'     => $quantity,
            ]);
        });
    }

    /**
     * Update cart item quantity
     */
    public function update(Cart $cart, CartItem $item, int $quantity): void
    {
        if ($item->cart_id !== $cart->id) {
            throw new ApiException('Unauthorized cart item', 403);
        }

        if ($quantity < 1) {
            throw new ApiException('Invalid quantity', 422);
        }

        $item->update([
            'quantity' => $quantity,
        ]);
    }

    /**
     * Remove item from cart
     */
    public function remove(Cart $cart, CartItem $item): void
    {
        if ($item->cart_id !== $cart->id) {
            throw new ApiException('Unauthorized cart item', 403);
        }

        $item->delete();
    }
}
