<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ampliar cash_movements con category y vínculo a expenses
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->string('category', 50)->default('traslado_boveda')->after('type')
                ->comment('fondo_extra, traslado_boveda, gasto_operativo');
            $table->foreignId('expense_id')->nullable()->after('category')
                ->constrained('expenses')->nullOnDelete();
        });

        // 2. Desglosar gastos operativos vs traslados a bóveda en cash_shifts
        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->decimal('total_expenses_cash', 12, 2)->default(0.00)->after('total_cash_out')
                ->comment('Gastos menores de operación pagados en efectivo desde caja');
            $table->decimal('total_vault_cash', 12, 2)->default(0.00)->after('total_expenses_cash')
                ->comment('Traslados internos a bóveda o banco');
        });
    }

    public function down(): void
    {
        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->dropColumn(['total_expenses_cash', 'total_vault_cash']);
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn(['category', 'expense_id']);
        });
    }
};
