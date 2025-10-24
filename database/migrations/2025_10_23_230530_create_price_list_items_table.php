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
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable(); // precio a nivel producto
            $table->unsignedBigInteger('variant_id')->nullable(); // (futuro) precio a nivel variante
            $table->decimal('price', 10, 2);
            $table->timestamps();

            // Evitar duplicados por (price_list_id, product_id, variant_id)
            $table->unique(['price_list_id', 'product_id', 'variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
