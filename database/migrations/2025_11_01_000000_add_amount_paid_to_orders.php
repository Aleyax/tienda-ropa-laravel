<?php
// database/migrations/2025_11_01_000000_add_amount_paid_to_orders.php
return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $t) {
            if (!Schema::hasColumn('orders','amount_paid')) {
                $t->decimal('amount_paid', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('orders','paid_at')) {
                $t->timestamp('paid_at')->nullable();
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
