<?php
// database/migrations/2025_10_28_000001_add_responsible_to_pick_baskets.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pick_baskets')) {
            Schema::table('pick_baskets', function (Blueprint $table) {
                if (!Schema::hasColumn('pick_baskets', 'responsible_user_id')) {
                    $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete()->after('warehouse_id');
                }
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('pick_baskets') && Schema::hasColumn('pick_baskets', 'responsible_user_id')) {
            Schema::table('pick_baskets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('responsible_user_id');
            });
        }
    }
};
