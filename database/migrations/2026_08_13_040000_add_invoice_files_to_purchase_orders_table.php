<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('invoice_pdf_path', 255)->nullable()->after('issued_at')
                  ->comment('Ruta relativa del PDF de factura/RV');
            $table->string('invoice_xml_path', 255)->nullable()->after('invoice_pdf_path')
                  ->comment('Ruta relativa del XML CFDI de factura/RV');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['invoice_pdf_path', 'invoice_xml_path']);
        });
    }
};
