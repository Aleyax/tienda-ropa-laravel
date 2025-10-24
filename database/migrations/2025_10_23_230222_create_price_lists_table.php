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
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('customer_groups')->cascadeOnDelete();
            $table->string('name');              // ej. "Lista Minorista", "Lista Mayorista"
            $table->string('currency')->default('PEN');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['group_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_lists');
    }
};
