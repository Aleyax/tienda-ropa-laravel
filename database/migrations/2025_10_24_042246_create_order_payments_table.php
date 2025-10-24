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
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('method'); // transfer|cod|online
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending|validated|failed
            $table->string('provider_ref')->nullable();   // id pasarela
            $table->string('evidence_url')->nullable();   // voucher/qr foto
            $table->unsignedBigInteger('collected_by')->nullable(); // repartidor/admin
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
