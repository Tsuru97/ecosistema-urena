<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Folio del documento del proveedor (RV / Factura fiscal)
            $table->string('rv_folio', 50)->nullable()->after('folio')
                  ->comment('Folio del documento del proveedor (RV/Factura)');

            // Fecha de emisión del documento del proveedor
            $table->date('issued_at')->nullable()->after('rv_folio')
                  ->comment('Fecha de emisión del documento del proveedor');

            // Campos financieros (equivalente a hoja Resumen del Excel)
            $table->decimal('gross_amount', 12, 2)->default(0)->after('total')
                  ->comment('Importe bruto antes de descuentos');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('gross_amount')
                  ->comment('Total descuento aplicado');
            $table->decimal('net_amount', 12, 2)->default(0)->after('discount_amount')
                  ->comment('Importe neto (gross - discount)');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('net_amount')
                  ->comment('Monto total pagado acumulado');

            // Estados (equivalente a columnas Estatus del Excel)
            $table->enum('payment_status', ['pendiente', 'pago_parcial', 'pagada'])
                  ->default('pendiente')->after('amount_paid');
            $table->enum('unit_status', ['incompleta', 'completa'])
                  ->default('incompleta')->after('payment_status');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            // Cantidades adicionales (equivalente a columnas del Excel)
            $table->integer('quantity_paid')->default(0)->after('quantity_received')
                  ->comment('Cant. Pagada');
            $table->integer('quantity_returned')->default(0)->after('quantity_paid')
                  ->comment('Cant. Devuelta');

            // Descuentos por línea
            $table->decimal('discount_percent', 5, 2)->default(0)->after('cost')
                  ->comment('% Descuento aplicado a esta línea');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_percent')
                  ->comment('Monto descuento en esta línea');

            // Importes calculados
            $table->decimal('gross_amount', 12, 2)->default(0)->after('discount_amount')
                  ->comment('Importe bruto de la línea (qty * cost)');
            $table->decimal('net_amount', 12, 2)->default(0)->after('gross_amount')
                  ->comment('Importe neto de la línea (gross - discount)');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('net_amount')
                  ->comment('Monto pagado de esta línea');

            // Estados por línea
            $table->enum('line_status', ['abierta', 'cerrada', 'cancelada'])
                  ->default('abierta')->after('amount_paid');
            $table->enum('unit_status', ['incompleta', 'completa'])
                  ->default('incompleta')->after('line_status');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'rv_folio', 'issued_at', 'gross_amount', 'discount_amount',
                'net_amount', 'amount_paid', 'payment_status', 'unit_status',
            ]);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'quantity_paid', 'quantity_returned', 'discount_percent', 'discount_amount',
                'gross_amount', 'net_amount', 'amount_paid', 'line_status', 'unit_status',
            ]);
        });
    }
};
