<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Yajra\DataTables\Facades\DataTables;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // show transactions with DataTables
        if (request()->ajax()) {
            $query = Transaction::with(['user']);

            return DataTables::of($query)
                ->addColumn('action', function ($item) {
                    return '
                        <a class="inline-block border border-blue-700 bg-blue-700 text-white rounded-md px-2 py-1 m-1 transition duration-500 ease select-none hover:bg-blue-800 focus:outline-none focus:shadow-outline"
                            href="' . route('dashboard.transaction.show', $item->id) . '">
                            Show
                        </a>
                        <a class="inline-block border border-gray-700 bg-gray-700 text-white rounded-md px-2 py-1 m-1 transition duration-500 ease select-none hover:bg-gray-800 focus:outline-none focus:shadow-outline"
                            href="' . route('dashboard.transaction.edit', $item->id) . '">
                            Edit
                        </a>';
                })
                ->editColumn('total_price', function ($item) {
                    $totalPrice = $item->total_price;

                    if (is_numeric($totalPrice)) {
                        return 'Rp. ' . number_format($totalPrice, 0, ',', '.');
                    }
                    return 'Rp. 0';  // fallback jika bukan angka
                })
                ->editColumn('status', function ($item) {
                    return $item->status ? 'Pending' : 'Failed';
                })
                ->rawColumns(['action'])
                ->make();
        }

        return view('pages.dashboard.transaction.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        //
        if (request()->ajax()) {
            $query = TransactionItem::with(['product'])->where('transaction_id', $transaction->id);
            
            return DataTables::of($query)
                ->editColumn('product.price', function ($item) {
                    $price = $item->product->price;

                    if (is_numeric($price)) {
                        return 'Rp. ' . number_format($price, 0, ',', '.');
                    }
                    return 'Rp. 0';  // fallback jika bukan angka
                })
                ->make();
        }

        return view('pages.dashboard.transaction.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        return view('pages.dashboard.transaction.edit', [
            'item' => $transaction
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionRequest $request, Transaction $transaction)
    {
        //Update transaction
        $data = $request->all();

        $transaction->update($data);
        
        return redirect()->route('dashboard.transaction.index')->with('success', 'Transaction updated successfully');
    
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}
