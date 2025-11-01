<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $t) {
            if (!Schema::hasColumn('orders','amount_paid')) {
                $t->decimal('amount_paid', 12, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('orders','paid_at')) {
                $t->timestamp('paid_at')->nullable()->after('amount_paid');
            }
        });
    }

    public function down(): void {
        Schema::table('orders', function (Blueprint $t) {
            if (Schema::hasColumn('orders','amount_paid')) $t->dropColumn('amount_paid');
            if (Schema::hasColumn('orders','paid_at')) $t->dropColumn('paid_at');
        });
    }
};
