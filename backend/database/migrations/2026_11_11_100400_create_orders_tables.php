<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('payment_method', 20);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->char('currency', 3)->default('BDT');
            $table->string('customer_name', 120);
            $table->string('customer_phone', 20);
            $table->string('shipping_address', 500);
            $table->string('notes', 1000)->nullable();
            $table->timestamp('placed_at');
            $table->timestamps();

            $table->index(['user_id', 'placed_at']);
            $table->index(['shop_id', 'status', 'placed_at']);
            $table->index(['payment_status', 'placed_at']);
            $table->index('placed_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shop_product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots: order history must not change when a listing changes later.
            $table->string('product_name', 255);
            $table->string('variant_label', 191)->nullable();
            $table->string('sku', 64)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 30);
            // Our transaction id sent to the gateway; unique => idempotent callbacks.
            $table->string('transaction_id', 64)->unique();
            $table->string('gateway_reference', 128)->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->string('status', 20)->default('pending');
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
