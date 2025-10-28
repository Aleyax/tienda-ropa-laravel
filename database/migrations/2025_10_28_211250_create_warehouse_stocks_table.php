<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_stocks')) {
            Schema::create('warehouse_stocks', function (Blueprint $table) {
                $table->id();

                $table->foreignId('warehouse_id')
                      ->constrained('warehouses')
                      ->cascadeOnDelete();

                $table->foreignId('variant_id')
                      ->constrained('product_variants')
                      ->cascadeOnDelete();

                // Existencias por almacén
                $table->unsignedInteger('on_hand')->default(0);   // físico en almacén
                $table->unsignedInteger('reserved')->default(0);  // reservado (opcional, útil para mayorista)

                $table->timestamps();

                $table->unique(['warehouse_id','variant_id']);
                $table->index(['variant_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');
    }
};
