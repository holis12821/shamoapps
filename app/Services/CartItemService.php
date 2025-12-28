<?php

namespace App\Services;

use App\Models\{Cart, CartItem, Product};
use Illuminate\Support\Facades\DB;
use App\Exceptions\ApiException;

class CartItemService
{
    public function add(Cart $cart, array $data): void
    {
        if (!isset($data['product_id'], $data['quantity'])) {
            throw new ApiException('Invalid payload', 422);
        }

        $product = Product::findOrFail($data['product_id']);

        CartItem::updateOrCreate(
            [
                'carts_id' => $cart->id,
                'products_id' => $product->id,
            ],
            [
                'product_name' => $product->name,
                'price' => $product->price,
                'quantity' => DB::raw('quantity + ' . (int) $data['quantity']),
            ]
        );
    }

    public function update(Cart $cart, CartItem $item, int $quantity): void
    {
        if ($item->cart_id !== $cart->id) {
            throw new ApiException('Unauthorized item', 403);
        }

        if ($quantity < 1) {
            throw new ApiException('Invalid quantity', 422);
        }

        $item->update([
            'quantity' => $quantity,
        ]);
    }
}