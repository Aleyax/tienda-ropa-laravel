<?php
// database/migrations/2025_10_28_000002_create_pick_basket_transfers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Crear solo si no existe
        if (! Schema::hasTable('pick_basket_transfers')) {
            Schema::create('pick_basket_transfers', function (Blueprint $table) {
                $table->id();

                $table->foreignId('pick_basket_id')
                    ->constrained('pick_baskets')
                    ->cascadeOnDelete();

                $table->foreignId('from_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('to_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string('status', 20)->default('pending'); // pending|accepted|declined|cancelled|expired
                $table->text('note')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();
            });
        } else {
            // 2) Si la tabla ya existe, asegurar columnas mínimas (por si fue creada a mano o incompleta)
            Schema::table('pick_basket_transfers', function (Blueprint $table) {
                if (!Schema::hasColumn('pick_basket_transfers', 'pick_basket_id')) {
                    $table->foreignId('pick_basket_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('pick_baskets')
                        ->cascadeOnDelete();
                }
                if (!Schema::hasColumn('pick_basket_transfers', 'from_user_id')) {
                    $table->foreignId('from_user_id')
                        ->nullable()
                        ->after('pick_basket_id')
                        ->constrained('users')
                        ->cascadeOnDelete();
                }
                if (!Schema::hasColumn('pick_basket_transfers', 'to_user_id')) {
                    $table->foreignId('to_user_id')
                        ->nullable()
                        ->after('from_user_id')
                        ->constrained('users')
                        ->cascadeOnDelete();
                }
                if (!Schema::hasColumn('pick_basket_transfers', 'status')) {
                    $table->string('status', 20)->default('pending')->after('to_user_id');
                }
                if (!Schema::hasColumn('pick_basket_transfers', 'note')) {
                    $table->text('note')->nullable()->after('status');
                }
                if (!Schema::hasColumn('pick_basket_transfers', 'decided_at')) {
                    $table->timestamp('decided_at')->nullable()->after('note');
                }
                if (!Schema::hasColumn('pick_basket_transfers', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        // 3) Índice lógico (si no existe)
        try {
            Schema::table('pick_basket_transfers', function (Blueprint $table) {
                $table->index(['pick_basket_id', 'status'], 'pbt_basket_status_idx');
            });
        } catch (\Throwable $e) {
            // si ya existe el índice, ignoramos
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pick_basket_transfers');
    }
};
