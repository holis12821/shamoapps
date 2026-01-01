<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductDetailController extends Controller
{
    public function show(string $id)
    {
        $product = Product::with(['category', 'galleries'])
            ->where('id', $id)
            ->first();

        if (!$product) {
            return ResponseFormatter::error(
                null,
                'Product not found',
                404
            );
        }

        return ResponseFormatter::success(
            [
                'product' => $product,
            ],
            'Detail product berhasil diambil'
        );
    }
}
