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
        Schema::table('inventories', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('store_id')->constrained('warehouses')->nullOnDelete();
            $table->integer('reserved_quantity')->default(0)->after('quantity')->comment('Cantidad reservada para apartados activos');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('store_id')->constrained('warehouses')->nullOnDelete();
            $table->foreignId('target_warehouse_id')->nullable()->after('warehouse_id')->constrained('warehouses')->nullOnDelete()->comment('En traspasos internos entre bodegas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['target_warehouse_id']);
            $table->dropColumn(['warehouse_id', 'target_warehouse_id']);
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['warehouse_id', 'reserved_quantity']);
        });
    }
};
