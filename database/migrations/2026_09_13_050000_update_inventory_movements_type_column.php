<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar 'type' de ENUM rígido a VARCHAR(50) para admitir 'reserva_apartado', 'liberacion_apartado', etc.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE inventory_movements MODIFY COLUMN type VARCHAR(50) NOT NULL COMMENT 'entrada, salida, ajuste, venta, compra, reserva_apartado, liberacion_apartado, etc.'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE inventory_movements MODIFY COLUMN type ENUM(
                'entrada', 'salida', 'ajuste', 'traspaso_salida', 'traspaso_entrada',
                'compra', 'venta', 'devolucion_venta', 'devolucion_compra'
            ) NOT NULL");
        }
    }
};
