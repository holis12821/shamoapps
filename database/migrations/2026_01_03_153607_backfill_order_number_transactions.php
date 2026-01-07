<?php

use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Transaction::whereNull('order_number')->chunkById(100, function ($transactions) {
            foreach ($transactions as $transaction) {
                $transaction->update([
                    'order_number' => Transaction::generateOrderNumber(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
