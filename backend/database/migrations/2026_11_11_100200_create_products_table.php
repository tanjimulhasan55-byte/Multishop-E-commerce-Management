<?php

use App\Support\Database\TranslatableColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The shared product catalog. Products are created by admins or shop owners
 * and can be listed by any shop through `shop_products`.
 */
return new class extends Migration
{
    use TranslatableColumns;

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('name');
            $this->englishShadowColumn($table, 'name');
            $table->string('slug', 191)->unique();
            $table->string('sku', 64)->nullable()->unique();
            $table->json('description')->nullable();
            // Suggested retail price; each shop sets its own price on its listing.
            $table->decimal('base_price', 12, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['category_id', 'status']);
            $table->index(['created_by', 'status']);
            $table->index('name_en');

            if ($this->supportsFullText()) {
                $table->fullText(['name_en', 'sku']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
