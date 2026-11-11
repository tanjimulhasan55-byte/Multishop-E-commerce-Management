<?php

use App\Support\Database\TranslatableColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use TranslatableColumns;

    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            // A shop may exist before an owner is assigned, and removing an
            // owner must never cascade-delete a shop with order history.
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('name');
            $this->englishShadowColumn($table, 'name');
            $table->string('slug', 191)->unique();
            $table->json('description')->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('name_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
