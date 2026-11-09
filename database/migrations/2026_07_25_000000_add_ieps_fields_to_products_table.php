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
        Schema::table('products', function (Blueprint $table) {
            $table->enum('ieps_type', ['exempt', 'percentage', 'fixed_quota'])->default('exempt')->after('base_cost')->comment('Tipo de IEPS del producto');
            $table->decimal('ieps_value', 10, 4)->default(0.0000)->after('ieps_type')->comment('Tasa porcentual (ej 0.08) o Cuota Fija por unidad/litro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['ieps_type', 'ieps_value']);
        });
    }
};
