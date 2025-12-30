<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function all(Request $request)
    {
        $limit = (int) $request->query('limit', 6);

        $products = Product::with(['category', 'galleries'])
            ->when($request->query('name'), function ($q, $name) {
                $q->where('name', 'like', "%{$name}%");
            })
            ->when($request->query('description'), function ($q, $description) {
                $q->where('description', 'like', "%{$description}%");
            })
            ->when($request->query('tags'), function ($q, $tags) {
                $q->where('tags', 'like', "%{$tags}%");
            })
            ->when($request->query('categories'), function ($q, $categoryId) {
                $q->where('categories_id', $categoryId);
            })
            ->when($request->query('price_from'), function ($q, $priceFrom) {
                $q->where('price', '>=', $priceFrom);
            })
            ->when($request->query('price_to'), function ($q, $priceTo) {
                $q->where('price', '<=', $priceTo);
            })
            ->orderBy('id', 'desc')
            ->paginate($limit)
            ->withQueryString();

        return ResponseFormatter::success([
            'items' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_more' => $products->hasMorePages(),
            ]
        ], 'Product List retrieved successfully');
    }
}
