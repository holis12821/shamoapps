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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            /**
             * cart_id
             * -----------------------------------
             * Relation to carts
             * If cart is deleted → item is also deleted
             */

            $table->foreignId('cart_id')
                ->constrained('carts')
                ->cascadeOnDelete();

            /**
             * product_id
             * -----------------------------------
             * Id Product ID (FK to products if any)
             */
            $table->unsignedBigInteger('product_id');

            /**
             * Snapshot product
             * -----------------------------------
             * So that the cart does not change even if the product is updated
             */
            $table->string('product_name');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');

            $table->timestamps();

            /**
             * One product can only be added to the cart once.
             */
            $table->unique(['cart_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
