<?php
// database/migrations/2025_11_01_000001_alter_paid_at_on_orders.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('paid_at')->nullable()->change();
        });
    }
    public function down() {
        Schema::table('orders', function (Blueprint $table) {
            // Si necesitas volver atrás, ajusta al tipo previo
            $table->dateTime('paid_at')->nullable()->change();
        });
    }
};
