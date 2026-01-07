<?php

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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Relation to transactions table
            $table->foreignId('transactions_id')
                ->constrained('transactions')
                ->cascadeOnDelete();

            // Midtrans metadata
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_url')->nullable();

            $table->enum('status', [
                'pending',
                'paid',
                'expired',
                'failed',
                'cancelled',
                'refunded',
            ])->default('pending');

            $table->timestamp('transaction_time')->nullable();
            $table->timestamp('settlement_time')->nullable();
            $table->string('fraud_status')->nullable();

            // Save payload callback Midtrans (audit / debug)
            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('transactions_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
