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
}
