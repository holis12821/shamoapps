<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    /**
     * List transaksi user (paginated)
     */
    /**
     * List transaksi user (paginated)
     */
    public function index(Request $request)
    {
        $limit  = (int) $request->query('limit', 6);
        $status = $request->query('status'); // pending, paid, expired, etc
        $sort   = $request->query('sort', 'latest'); // latest | oldest

        $transactions = Transaction::with(['items.product.galleries'])
            ->where('users_id', Auth::id()) // 🔐 User isolation
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy(
                'created_at',
                $sort === 'oldest' ? 'asc' : 'desc'
            )
            ->paginate($limit)
            ->withQueryString();

        return ResponseFormatter::success(
            [
                'transactions' => collect($transactions->items())->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'status' => $transaction->status,
                        'order_number' => $transaction->order_number,
                        'total_price' => $transaction->total_price,
                        'shipping_price' => $transaction->shipping_price,
                        'grand_total' => $transaction->grand_total,
                        'created_at' => $transaction->created_at,

                        'formatted' => $transaction->formatted,

                        'items' => $transaction->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                                'subtotal' => $item->subtotal,
                                'formatted' => $item->formatted,

                                'product' => [
                                    'id' => $item->product->id,
                                    'name' => $item->product->name,
                                    'price' => $item->product->price,
                                    'formatted' => $item->product->formatted,
                                    'galleries' => $item->product->galleries->map(fn ($g) => [
                                        'url' => $g->url,
                                    ]),
                                ],
                            ];
                        }),
                    ];
                }),
            ],
            'Transaction list retrieved successfully',
            200,
            [
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'has_more' => $transactions->hasMorePages(),
                ],
                'filters' => [
                    'status' => $status,
                    'sort' => $sort,
                ],
            ]
        );
    }
}
