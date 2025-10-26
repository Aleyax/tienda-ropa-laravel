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
        Schema::table('order_items', function (Blueprint $table) {
            Schema::table('order_items', function (Blueprint $table) {
                // Agrega 'name' si no existe
                if (!Schema::hasColumn('order_items', 'name')) {
                    $table->string('name')->after('variant_id');
                }

                // Agrega 'sku' si no existe
                if (!Schema::hasColumn('order_items', 'sku')) {
                    $table->string('sku')->nullable()->after('name');
                }

                // Agrega 'unit_price' si no existe
                if (!Schema::hasColumn('order_items', 'unit_price')) {
                    $table->decimal('unit_price', 10, 2)->default(0)->after('sku');
                }

                // Agrega 'qty' si por alguna razón no existiera (ya lo tienes, pero por si acaso)
                if (!Schema::hasColumn('order_items', 'qty')) {
                    $table->unsignedInteger('qty')->default(1)->after('unit_price');
                }

                // Agrega 'backorder_qty' si no existe (tú ya la migraste, esto es idempotente)
                if (!Schema::hasColumn('order_items', 'backorder_qty')) {
                    $table->unsignedInteger('backorder_qty')->default(0)->after('qty');
                }

                // Agrega 'amount' si no existe
                if (!Schema::hasColumn('order_items', 'amount')) {
                    $table->decimal('amount', 10, 2)->default(0)->after('backorder_qty');
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Solo elimina si existen, para que sea seguro
            if (Schema::hasColumn('order_items', 'amount')) $table->dropColumn('amount');
            if (Schema::hasColumn('order_items', 'backorder_qty')) $table->dropColumn('backorder_qty');
            if (Schema::hasColumn('order_items', 'qty')) $table->dropColumn('qty');
            if (Schema::hasColumn('order_items', 'unit_price')) $table->dropColumn('unit_price');
            if (Schema::hasColumn('order_items', 'sku')) $table->dropColumn('sku');
            if (Schema::hasColumn('order_items', 'name')) $table->dropColumn('name');
        });
    }
};
