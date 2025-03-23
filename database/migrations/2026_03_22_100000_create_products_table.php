<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 0);
            $table->string('category');
            $table->string('brand');
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->decimal('rating', 2, 1)->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('brand');
            $table->index('price');
            $table->index('rating');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
