<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('transfer_msi_cost_to_client')->default(false)->after('accepts_financing')->comment('True si la sobretasa de MSI se cobra al cliente en vez de ser gasto de la empresa');
            $table->boolean('apply_iva_to_rates')->default(true)->after('transfer_msi_cost_to_client')->comment('True si se debe sumar el 16% de IVA a las comisiones y sobretasas bancarias');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['transfer_msi_cost_to_client', 'apply_iva_to_rates']);
        });
    }
};
