<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A "listing": a catalog product offered by one shop, with shop-specific
 * title/description overrides and thumbnail. Each variant of the product
 * gets its own shop price and stock in `shop_product_variants`.
 *
 * Customers can only see products that have an active listing in an active
 * shop — a catalog product with no listing is never exposed publicly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Denormalized from shop_product_variants (lowest active price,
            // total active stock) so storefront sort / price / in-stock
            // filters stay single-table index scans. Maintained by
            // App\Actions\Listings\RefreshListingAggregatesAction.
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // A shop lists a catalog product at most once.
            $table->unique(['shop_id', 'product_id']);
            // Storefront: "active listings of shop X sorted by price / newest".
            $table->index(['shop_id', 'is_active', 'price']);
            $table->index(['shop_id', 'is_active', 'created_at']);
            $table->index(['is_active', 'price']);
            // Catalog: "which shops list product Y".
            $table->index(['product_id', 'is_active']);
        });

        Schema::create('shop_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_product_id', 'product_variant_id']);
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_product_variants');
        Schema::dropIfExists('shop_products');
    }
};
