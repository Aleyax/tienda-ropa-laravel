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
            // Selección logística
            $table->string('shipping_mode')->default('deposit'); // 'pickup' | 'deposit' | 'to_be_quoted'

            // Dirección / zona / tarifa
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete();
            $table->foreignId('shipping_rate_id')->nullable()->constrained('shipping_rates')->nullOnDelete();

            // Montos
            $table->decimal('shipping_estimated', 10, 2)->nullable(); // lo mostrado como referencia
            $table->decimal('shipping_deposit', 10, 2)->default(0);   // lo que cobras hoy (si aplica)
            $table->decimal('shipping_actual', 10, 2)->nullable();    // costo real courier
            $table->decimal('shipping_amount', 10, 2)->default(0);    // lo cobrado hoy (entra a grand_total)
            $table->decimal('grand_total', 10, 2)->default(0);        // total + envío cobrado hoy

            // Liquidación posterior
            $table->string('shipping_settlement_status')->default('unsettled'); // unsettled|refund_due|additional_due|settled
            $table->timestamp('settled_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
