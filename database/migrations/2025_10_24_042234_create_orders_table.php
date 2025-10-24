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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method');   // 'transfer' | 'cod' | 'online'
            $table->string('payment_status')->default('unpaid'); // unpaid|pending_confirmation|cod_promised|paid|failed|partially_paid
            $table->string('status')->default('new'); // new|confirmed|preparing|shipped|delivered|cancelled
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->json('cod_details')->nullable();   // { "pay_type":"cash|yape|plin", "change":"..." }
            $table->string('voucher_url')->nullable(); // transferencia
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
