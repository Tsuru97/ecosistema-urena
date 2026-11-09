<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Tipo de movimiento
            $table->string('type', 50);

            $table->enum('direction', ['in', 'out']); // Simplifica reportes
            $table->integer('quantity');               // Siempre positivo
            $table->integer('quantity_before');        // Stock antes del movimiento
            $table->integer('quantity_after');         // Stock después del movimiento

            // Relación polimórfica al documento de origen (Transfer, PurchaseOrder, Sale, etc.)
            $table->nullableMorphs('reference');       // reference_type + reference_id

            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // Quién registró

            $table->timestamps();

            $table->index(['store_id', 'product_id']);
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
