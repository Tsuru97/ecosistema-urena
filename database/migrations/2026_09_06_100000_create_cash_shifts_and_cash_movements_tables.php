<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Tabla de Turnos y Cortes de Caja
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->comment('Cajero responsable');
            $table->foreignId('closed_by')->nullable()->constrained('users')->comment('Usuario/supervisor que cierra el turno');
            $table->string('folio')->unique();
            $table->decimal('opening_amount', 12, 2)->default(0.00)->comment('Fondo inicial de caja');
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->enum('status', ['abierto', 'cerrado'])->default('abierto');
            
            // Totales de cierre y arqueo
            $table->decimal('expected_cash', 12, 2)->default(0.00)->comment('Efectivo esperado según sistema');
            $table->decimal('actual_cash', 12, 2)->nullable()->comment('Efectivo contado físicamente por cajero');
            $table->decimal('difference_cash', 12, 2)->default(0.00)->comment('Diferencia (+ sobrante, - faltante)');
            
            // Desglose consolidado por formas de pago
            $table->decimal('total_sales_cash', 12, 2)->default(0.00);
            $table->decimal('total_sales_card', 12, 2)->default(0.00);
            $table->decimal('total_sales_transfer', 12, 2)->default(0.00);
            $table->decimal('total_sales_other', 12, 2)->default(0.00);
            
            // Anticipos y apartados
            $table->decimal('total_layaways_cash', 12, 2)->default(0.00);
            $table->decimal('total_special_orders_cash', 12, 2)->default(0.00);
            
            // Entradas y salidas manuales de efectivo (sangrías / fondo extra)
            $table->decimal('total_cash_in', 12, 2)->default(0.00);
            $table->decimal('total_cash_out', 12, 2)->default(0.00);
            
            // Gran total y métricas
            $table->decimal('total_sales_amount', 12, 2)->default(0.00);
            $table->unsignedInteger('sales_count')->default(0);
            
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['store_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // 2. Tabla de Movimientos Manuales de Efectivo (Entradas / Sangrías)
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_shift_id')->constrained('cash_shifts')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('type', ['entrada', 'salida'])->comment('entrada: ingreso manual, salida: retiro/sangría');
            $table->decimal('amount', 12, 2);
            $table->string('concept');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['cash_shift_id', 'type']);
        });

        // 3. Vincular cash_shift_id a sales
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cash_shift_id')->nullable()->after('store_id')->constrained('cash_shifts')->nullOnDelete();
        });

        // 4. Vincular cash_shift_id a payments
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('cash_shift_id')->nullable()->after('sale_id')->constrained('cash_shifts')->nullOnDelete();
        });

        // 5. Vincular cash_shift_id a special_order_payments
        Schema::table('special_order_payments', function (Blueprint $table) {
            $table->foreignId('cash_shift_id')->nullable()->after('special_order_id')->constrained('cash_shifts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('special_order_payments', function (Blueprint $table) {
            $table->dropForeign(['cash_shift_id']);
            $table->dropColumn('cash_shift_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['cash_shift_id']);
            $table->dropColumn('cash_shift_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cash_shift_id']);
            $table->dropColumn('cash_shift_id');
        });

        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_shifts');
    }
};
