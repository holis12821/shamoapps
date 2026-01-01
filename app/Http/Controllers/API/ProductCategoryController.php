<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function all(Request $request)
    {
        $limit = (int) $request->query('limit', 6);
        $name  = $request->query('name');

        $categories = ProductCategory::query()
            ->when($name, fn ($q) =>
                $q->where('name', 'like', "%{$name}%")
            )
            ->orderBy('created_at', 'desc')
            ->paginate($limit)
            ->withQueryString();

        return ResponseFormatter::success(
            [
                'categories' => $categories->items(),
            ],
            'Product categories retrieved successfully',
            200,
            [
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'has_more' => $categories->hasMorePages(),
                ],
                'filters' => $request->query(),
            ]
        );
    }
}