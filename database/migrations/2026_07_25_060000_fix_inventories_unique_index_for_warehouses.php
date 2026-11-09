<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;
use App\Models\Store;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Asignar bodega predeterminada a cualquier registro antiguo de inventario con warehouse_id nulo
        $stores = Store::all();
        foreach ($stores as $store) {
            Warehouse::ensureDefaultWarehousesForStore($store->id);
            $defaultWh = Warehouse::where('store_id', $store->id)->where('is_default', true)->first();
            if ($defaultWh) {
                DB::table('inventories')
                    ->where('store_id', $store->id)
                    ->whereNull('warehouse_id')
                    ->update(['warehouse_id' => $defaultWh->id]);
            }
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropUnique('inv_store_prod_var_unique');
                $table->unique(['store_id', 'warehouse_id', 'product_id', 'product_variant_id'], 'inv_store_wh_prod_var_unique');
            });
            return;
        }

        // 2. Obtener los nombres reales de índices existentes en la tabla inventories
        $existingIndexes = collect(DB::select("SHOW INDEXES FROM inventories"))
            ->pluck('Key_name')
            ->unique();

        // 3. Eliminar únicamente los índices únicos antiguos si están presentes en la BD
        Schema::table('inventories', function (Blueprint $table) use ($existingIndexes) {
            if ($existingIndexes->contains('inv_store_prod_var_unique')) {
                $table->dropUnique('inv_store_prod_var_unique');
            }
            if ($existingIndexes->contains('inventories_store_id_product_id_product_variant_id_unique')) {
                $table->dropUnique('inventories_store_id_product_id_product_variant_id_unique');
            }
            if ($existingIndexes->contains('inventories_store_id_product_id_unique')) {
                $table->dropUnique('inventories_store_id_product_id_unique');
            }

            // 4. Crear el nuevo índice único multisucursal + multibodega si aún no existe
            if (!$existingIndexes->contains('inv_store_wh_prod_var_unique')) {
                $table->unique(['store_id', 'warehouse_id', 'product_id', 'product_variant_id'], 'inv_store_wh_prod_var_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingIndexes = collect(DB::select("SHOW INDEXES FROM inventories"))
            ->pluck('Key_name')
            ->unique();

        Schema::table('inventories', function (Blueprint $table) use ($existingIndexes) {
            if ($existingIndexes->contains('inv_store_wh_prod_var_unique')) {
                $table->dropUnique('inv_store_wh_prod_var_unique');
            }

            if (!$existingIndexes->contains('inv_store_prod_var_unique')) {
                $table->unique(['store_id', 'product_id', 'product_variant_id'], 'inv_store_prod_var_unique');
            }
        });
    }
};
