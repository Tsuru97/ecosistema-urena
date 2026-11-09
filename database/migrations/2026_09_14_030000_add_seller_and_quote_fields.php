<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Campos de vendedor en ventas
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'seller_id')) {
                $table->foreignId('seller_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->comment('Vendedor comisionista asignado a la venta');
            }
        });

        // 2. Campos de folio, vendedor y vigencia en cotizaciones
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'folio')) {
                $table->string('folio', 50)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('quotes', 'seller_id')) {
                $table->foreignId('seller_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->comment('Vendedor comisionista que elaboró la cotización');
            }
            if (!Schema::hasColumn('quotes', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'seller_id')) {
                $table->dropForeign(['seller_id']);
                $table->dropColumn('seller_id');
            }
        });

        Schema::table('quotes', function (Blueprint $table) {
            if (Schema::hasColumn('quotes', 'seller_id')) {
                $table->dropForeign(['seller_id']);
                $table->dropColumn('seller_id');
            }
            if (Schema::hasColumn('quotes', 'folio')) {
                $table->dropColumn('folio');
            }
            if (Schema::hasColumn('quotes', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};
