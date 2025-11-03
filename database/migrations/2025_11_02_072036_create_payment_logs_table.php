<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();

            // vínculo principal (puede ser nulo si logueas algo a nivel orden)
            $table->foreignId('order_payment_id')->nullable()->constrained('order_payments')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

            // quién hizo la acción
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            // evento y datos
            $table->string('event', 100); // p.ej: payment_created, payment_status_updated, evidence_deleted
            $table->json('old_payload')->nullable();
            $table->json('new_payload')->nullable();
            $table->json('meta')->nullable(); // ip, route, userAgent, etc.

            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['order_payment_id']);
            $table->index(['event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
