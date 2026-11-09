<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            
            $table->integer('quantity')->default(1);
            
            $table->decimal('base_cost', 12, 2)->default(0)->comment('Costo base del producto al cotizar');
            $table->decimal('unit_price_original', 12, 2)->default(0)->comment('Precio de lista sugerido');
            $table->decimal('unit_price_final', 12, 2)->default(0)->comment('Precio final tras regateos autorizados');
            
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('Impuesto unitario');
            
            $table->decimal('subtotal', 12, 2)->default(0)->comment('quantity * unit_price_final');
            $table->decimal('total', 12, 2)->default(0)->comment('subtotal + (tax_amount * quantity)');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
