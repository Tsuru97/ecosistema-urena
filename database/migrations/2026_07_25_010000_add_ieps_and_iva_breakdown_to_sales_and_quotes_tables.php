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
        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('ieps_total', 12, 2)->default(0)->after('subtotal')->comment('Monto total de IEPS en la cotización');
            $table->decimal('iva_total', 12, 2)->default(0)->after('ieps_total')->comment('Monto total de IVA en la cotización');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->decimal('ieps_amount', 12, 2)->default(0)->after('tax_amount')->comment('Monto de IEPS en la línea');
            $table->decimal('iva_amount', 12, 2)->default(0)->after('ieps_amount')->comment('Monto de IVA en la línea');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('ieps_total', 12, 2)->default(0)->after('subtotal')->comment('Monto total de IEPS en la venta');
            $table->decimal('iva_total', 12, 2)->default(0)->after('ieps_total')->comment('Monto total de IVA en la venta');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('ieps_amount', 12, 2)->default(0)->after('tax_amount')->comment('Monto de IEPS en la línea de venta');
            $table->decimal('iva_amount', 12, 2)->default(0)->after('ieps_amount')->comment('Monto de IVA en la línea de venta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['ieps_total', 'iva_total']);
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn(['ieps_amount', 'iva_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['ieps_total', 'iva_total']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['ieps_amount', 'iva_amount']);
        });
    }
};
