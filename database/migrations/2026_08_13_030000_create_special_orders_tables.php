<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_orders', function (Blueprint $table) {
            $table->id();

            $table->string('folio', 20)->unique()
                  ->comment('Folio interno del pedido especial: PED-YYYY-NNNN');

            $table->foreignId('store_id')
                  ->constrained('stores')
                  ->cascadeOnDelete();

            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->restrictOnDelete()
                  ->comment('Cliente requerido para trazabilidad');

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->restrictOnDelete()
                  ->comment('Vendedor que generó el pedido');

            $table->foreignId('quote_id')
                  ->nullable()
                  ->constrained('quotes')
                  ->nullOnDelete()
                  ->comment('Venta formal vinculada al entregar');

            $table->enum('status', [
                'pending',    // Creado, esperando OC
                'ordered',    // OC generada, esperando mercancía
                'partial',    // Mercancía parcialmente recibida
                'ready',      // Listo para entrega al cliente
                'delivered',  // Entregado y venta cerrada
                'cancelled',  // Cancelado
            ])->default('pending')->index();

            $table->text('notes')->nullable();

            $table->date('expected_delivery_date')->nullable()
                  ->comment('Fecha estimada de entrega al cliente');

            $table->decimal('total', 12, 2)->default(0)
                  ->comment('Total del pedido especial');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('special_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('special_order_id')
                  ->constrained('special_orders')
                  ->cascadeOnDelete();

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->restrictOnDelete();

            $table->foreignId('product_variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->nullOnDelete();

            // Vinculación OPCIONAL a la OC que surtirá este ítem
            $table->foreignId('purchase_order_id')
                  ->nullable()
                  ->constrained('purchase_orders')
                  ->nullOnDelete()
                  ->comment('OC que va a surtir este ítem (opcional)');

            $table->integer('quantity');
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_price', 12, 2);

            $table->text('notes')->nullable()
                  ->comment('Descripción o personalización del ítem');

            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('special_order_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('special_order_id')
                  ->constrained('special_orders')
                  ->cascadeOnDelete();

            $table->foreignId('payment_method_id')
                  ->constrained('payment_methods')
                  ->restrictOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->restrictOnDelete()
                  ->comment('Vendedor que registró el anticipo');

            $table->decimal('amount', 12, 2)
                  ->comment('Monto del anticipo del cliente');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_order_payments');
        Schema::dropIfExists('special_order_items');
        Schema::dropIfExists('special_orders');
    }
};
