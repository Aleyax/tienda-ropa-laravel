<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Asegura picked_qty
            if (! Schema::hasColumn('order_items', 'picked_qty')) {
                // la colocamos después de qty si existe, sino simple add
                try {
                    $table->unsignedInteger('picked_qty')->default(0)->after('qty');
                } catch (\Throwable $e) {
                    $table->unsignedInteger('picked_qty')->default(0);
                }
            }

            // Asegura unpicked_qty (si lo usas)
            if (! Schema::hasColumn('order_items', 'unpicked_qty')) {
                try {
                    $table->unsignedInteger('unpicked_qty')->default(0)->after('picked_qty');
                } catch (\Throwable $e) {
                    $table->unsignedInteger('unpicked_qty')->default(0);
                }
            }

            // Asegura picked_by (FK a users)
            if (! Schema::hasColumn('order_items', 'picked_by')) {
                try {
                    $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete()->after('unpicked_qty');
                } catch (\Throwable $e) {
                    // Si falla el after (por orden), agrega sin after
                    $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
                }
            }

            // Asegura picked_at (timestamp)
            if (! Schema::hasColumn('order_items', 'picked_at')) {
                try {
                    $table->timestamp('picked_at')->nullable()->after('picked_by');
                } catch (\Throwable $e) {
                    $table->timestamp('picked_at')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Eliminar en orden seguro
            if (Schema::hasColumn('order_items', 'picked_at')) {
                $table->dropColumn('picked_at');
            }

            if (Schema::hasColumn('order_items', 'picked_by')) {
                // si fue creado como FK, usar dropConstrainedForeignId
                try {
                    $table->dropConstrainedForeignId('picked_by');
                } catch (\Throwable $e) {
                    // fallback si no está como constrained
                    $table->dropColumn('picked_by');
                }
            }

            if (Schema::hasColumn('order_items', 'unpicked_qty')) {
                $table->dropColumn('unpicked_qty');
            }

            if (Schema::hasColumn('order_items', 'picked_qty')) {
                $table->dropColumn('picked_qty');
            }
        });
    }
};
