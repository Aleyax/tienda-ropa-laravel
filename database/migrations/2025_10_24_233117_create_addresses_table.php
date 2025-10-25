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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();           // "Casa", "Oficina"
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->default('PE');
            $table->string('region')->default('Lima');
            $table->string('province')->default('Lima');
            $table->string('district');
            $table->string('line1');                      // dirección
            $table->string('reference')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
