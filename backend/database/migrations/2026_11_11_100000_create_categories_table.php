<?php

use App\Support\Database\TranslatableColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Category tree: adjacency list (parent_id) + materialized path.
 *
 * `path` stores the ancestor chain, e.g. "/1/4/9/". "Category 4 and every
 * descendant" is then a single index range scan — `path LIKE '/1/4/%'` —
 * instead of recursive queries, while parent_id keeps writes simple.
 */
return new class extends Migration
{
    use TranslatableColumns;

    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: a category with children cannot be removed by accident.
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('path', 255)->default('/');
            $table->unsignedTinyInteger('depth')->default(0);
            $table->json('name');
            $this->englishShadowColumn($table, 'name');
            $table->string('slug', 191)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('path');
            $table->index(['parent_id', 'sort_order']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
