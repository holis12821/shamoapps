<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Support\Str;

class CartService
{
    public function create(): array
    {
        $cart = Cart::create([
            'public_id' => Str::uuid(),
            'secret_key' => hash_hmac(
                'sha256',
                Str::uuid(),
                config('app.key')
            ),
            'expires_at' => now()->addDays(24),
            'status' => 'active',
        ]);

        return [
            'cart_id' => $cart->public_id,
            'cart_secret' => $cart->secret_key,
            'expires_at' => $cart->expires_at,
        ];
    }

    /**
     * Get guest cart from secret
     */
    public function getGuestCart(): ?Cart
    {
        $secret = request()->header('X-Cart-Secret');

        if (! $secret) {
            return null;
        }

        return Cart::where('secret_key', $secret)
            ->whereNull('user_id')
            ->where('status', 'active')
            ->with('items')
            ->first();
    }

    /**
     * Ambil cart user (login)
     */
    public function getUserCart(int $userId): ?Cart
    {
        return Cart::where('user_id', $userId)
            ->where('status', 'active')
            ->with('items')
            ->first();
    }

    /**
     * Get active cart (user > guest)
     * This is an important helper
     */
    public function getActiveCart(?int $userId = null): ?Cart
    {
        if ($userId) {
            return $this->getUserCart($userId);
        }

        return $this->getGuestCart();
    }
}
