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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type', 20)->default('retail')->after('user_id'); // retail | wholesale
            }
            if (!Schema::hasColumn('orders', 'is_priority')) {
                $table->boolean('is_priority')->default(false)->after('status');
            }
            if (!Schema::hasColumn('orders', 'priority_level')) {
                $table->unsignedTinyInteger('priority_level')->default(0)->after('is_priority');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'priority_level')) {
                $table->dropColumn('priority_level');
            }
            if (Schema::hasColumn('orders', 'is_priority')) {
                $table->dropColumn('is_priority');
            }
            if (Schema::hasColumn('orders', 'order_type')) {
                $table->dropColumn('order_type');
            }
        });
    }
};
