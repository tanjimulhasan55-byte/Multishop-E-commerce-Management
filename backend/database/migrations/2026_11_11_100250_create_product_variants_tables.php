<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every catalog product has at least one variant (the sellable unit with its
 * own SKU). A simple product has a single default variant with no options;
 * a configurable product has one variant per option combination.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64)->unique();
            // Sorted attribute_value ids, e.g. "3-12" ("" for the default variant).
            // Unique per product => the same combination can never exist twice.
            $table->string('option_signature', 191)->default('');
            $table->decimal('base_price', 12, 2);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'option_signature']);
            $table->index(['product_id', 'position']);
        });

        Schema::create('attribute_value_product_variant', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            // restrict: an option value in use by a variant cannot be deleted.
            $table->foreignId('attribute_value_id')->constrained()->restrictOnDelete();

            $table->primary(['product_variant_id', 'attribute_value_id']);
            // Reverse lookup: "variants that are size XL" (storefront filters).
            $table->index('attribute_value_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_value_product_variant');
        Schema::dropIfExists('product_variants');
    }
};
