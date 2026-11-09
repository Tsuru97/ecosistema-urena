<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El índice UNIQUE en product_variants.sku bloquea la creación de nuevas
 * variantes con el mismo SKU cuando existe un registro soft-deleted con ese SKU.
 * MySQL aplica la restricción de unicidad a TODAS las filas (incluyendo eliminadas
 * suavemente), lo que hace imposible reutilizar un SKU tras un soft-delete.
 *
 * Decisión de diseño:
 * - Se elimina el índice único de BD y se conserva la integridad a nivel
 *   aplicación mediante Rule::unique()->whereNull('deleted_at') en la validación.
 * - Las variantes eliminadas conservan su SKU histórico intacto.
 * - Se agrega un índice compuesto (product_id, sku) para mantener eficiencia
 *   en las búsquedas más comunes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Eliminar el índice único global en sku
            $table->dropUnique('product_variants_sku_unique');

            // Agregar índice no-único para seguir buscando por SKU eficientemente
            $table->index('sku', 'product_variants_sku_index');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('product_variants_sku_index');
            $table->unique('sku', 'product_variants_sku_unique');
        });
    }
};
