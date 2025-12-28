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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            /**
             * public_id
             * -----------------------------------
             * Secure cart identifier for clients
             * Sent via header: X-CART-ID
             * NOT stored in the URL
             */
            $table->uuid('public_id')->unique();
            /**
             * secret_key
             * -----------------------------------
             * Proof of cart ownership
             * Used for:
             * - claiming cart
             * - checkout
             */
            $table->string('secret_key', 64);
            /**
             * user_id
             * -----------------------------------
             * Nullable because the cart can be anonymous
             * Filled in after the user logs in / claims the cart
             */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /**
             * user_id
             * -----------------------------------
             * Nullable because the cart can be anonymous
             * Filled in after the user logs in / claims the cart
             */
            $table->enum('status', ['active', 'checked_out', 'abandoned', 'expired'])
                ->default('active');

            /**
             * expires_at
             * -----------------------------------
             * Cart expiration time
             * Will be extended whenever there is activity
             */
            $table->timestamp('expires_at')->nullable();

            /**
             * device_fingerprint (optional)
             * -----------------------------------
             * Used for additional security
             * (e.g., tie the cart to the device)
             */
            $table->string('device_fingerprint', 255)->nullable();

            $table->timestamps();

             /**
             * Index for performance
             */
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
