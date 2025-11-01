<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_status_timestamps_to_orders_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders','confirmed_at'))  $table->timestamp('confirmed_at')->nullable()->after('updated_at');
            if (!Schema::hasColumn('orders','preparing_at'))  $table->timestamp('preparing_at')->nullable()->after('confirmed_at');
            if (!Schema::hasColumn('orders','shipped_at'))    $table->timestamp('shipped_at')->nullable()->after('preparing_at');
            if (!Schema::hasColumn('orders','delivered_at'))  $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            if (!Schema::hasColumn('orders','cancelled_at'))  $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
            // opcional si aún no existe:
            if (!Schema::hasColumn('orders','paid_at'))       $table->timestamp('paid_at')->nullable()->after('payment_status');
            // opcional si vas a guardar nota de cambio de estado:
            if (!Schema::hasColumn('orders','status_note'))   $table->string('status_note', 1000)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['confirmed_at','preparing_at','shipped_at','delivered_at','cancelled_at','status_note'] as $col) {
                if (Schema::hasColumn('orders',$col)) $table->dropColumn($col);
            }
        });
    }
};
