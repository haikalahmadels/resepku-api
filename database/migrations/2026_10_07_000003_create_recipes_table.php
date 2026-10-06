<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            // Pengunggah resep.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 100)->index();
            // Path relatif terhadap folder public, mis. "images/recipes/x.jpg"
            // atau "storage/recipes/x.jpg" untuk foto yang diunggah.
            $table->string('image');
            $table->unsignedSmallInteger('cooking_time_minutes');
            // Rata-rata rating, diperbarui setiap ada rating baru.
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('amount', 50);
            $table->unsignedSmallInteger('position')->default(0);
        });

        Schema::create('recipe_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->text('description');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_steps');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
    }
};
