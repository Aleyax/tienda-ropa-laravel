
<?php
// database/migrations/XXXX_XX_XX_XXXXXX_create_payments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // vínculo con la orden
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // datos del pago
            $table->string('method')->nullable();       // yape, plin, transferencia, efectivo, etc.
            $table->decimal('amount', 10, 2);           // monto del abono
            $table->string('status', 30)->default('pending_confirmation');
            // estados sugeridos: pending_confirmation | authorized | paid | failed | cancelled

            $table->string('reference')->nullable();    // nro operación, nota interna
            $table->string('voucher_url')->nullable();  // ruta/URL de la imagen/PDF del comprobante

            // auditoría mínima
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->nullable();

            $table->timestamps();

            // índices útiles
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
