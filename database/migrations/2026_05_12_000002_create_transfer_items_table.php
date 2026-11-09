<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('product_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('product_variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->restrictOnDelete();

            $table->integer('quantity_requested'); // Lo que se pide enviar
            $table->integer('quantity_sent')->nullable();     // Lo que realmente se envía
            $table->integer('quantity_received')->nullable(); // Lo que confirma la destino

            $table->text('notes')->nullable(); // Notas por línea (ej: "2 unidades dañadas")

            $table->timestamps();

            $table->index(['transfer_id', 'product_id']);
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
    }
};
