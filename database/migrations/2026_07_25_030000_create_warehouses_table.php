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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150)->comment('Ej. Piso de Exhibición, Bodega Principal, Bodega Auxiliar');
            $table->enum('type', ['exhibition', 'warehouse'])->default('warehouse')->comment('exhibition = Mueble armado en exhibición, warehouse = Almacén empacado');
            $table->boolean('allow_sale')->default(true)->comment('Determina si el POS permite vender directamente stock de esta bodega');
            $table->boolean('is_default')->default(false)->comment('Bodega predeterminada para recepciones y salidas en esta sucursal');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
