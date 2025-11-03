<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('order_payments', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
            // índices útiles (opcionales si no existen)
            if (!Schema::hasColumn('order_payments', 'status')) {
                $table->string('status', 30)->default('pending_confirmation')->after('amount');
            }
            $table->index(['order_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            if (Schema::hasColumn('order_payments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            // no tocamos status en down por seguridad
        });
    }
};
