<?php

use App\Support\Database\TranslatableColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global variant options, e.g. attribute "Size" with values S / M / L / XL,
 * attribute "Color" with values Red / Blue (with a swatch colour).
 */
return new class extends Migration
{
    use TranslatableColumns;

    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $this->englishShadowColumn($table, 'name');
            $table->string('slug', 191)->unique();
            $table->string('type', 20)->default('select'); // select | color
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('sort_order');
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->json('value');
            $this->englishShadowColumn($table, 'value');
            $table->string('slug', 191);
            // Short code used when generating variant SKUs (e.g. "XL", "RED").
            $table->string('code', 20);
            $table->char('color_hex', 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
            $table->unique(['attribute_id', 'code']);
            $table->index(['attribute_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
};
