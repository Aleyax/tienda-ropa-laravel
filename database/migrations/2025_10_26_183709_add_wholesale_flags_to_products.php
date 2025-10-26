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
        Schema::table('products', function (Blueprint $table) {
            // Ajusta la posición del ->after(...) si tu tabla no tiene 'status'
            $table->boolean('discontinued')->default(false)->after('status');
            $table->boolean('available_for_wholesale')->default(true)->after('discontinued');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['discontinued', 'available_for_wholesale']);
        });
    }
};
