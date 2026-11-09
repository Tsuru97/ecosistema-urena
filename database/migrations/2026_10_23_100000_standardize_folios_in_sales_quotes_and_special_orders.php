<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Sale;

return new class extends Migration
{
    /**
     * Estandariza los folios existentes de Ventas (VTA-YYYY-NNNN), Cotizaciones Formales (COT-YYYY-NNNN)
     * y Pedidos Especiales (PED-YYYY-NNNN), actualizando además las referencias en movimientos de inventario,
     * gastos, órdenes de compra y bitácora de auditoría.
     */
    public function up(): void
    {
        Sale::standardizeAllEcosystemFolios();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // La estandarización de folios es una normalización de formato; no requiere reversión destructiva.
    }
};
