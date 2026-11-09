<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_order_id')
                  ->constrained('purchase_orders')
                  ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                  ->constrained('suppliers')
                  ->restrictOnDelete();

            $table->foreignId('store_id')
                  ->constrained('stores')
                  ->restrictOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->restrictOnDelete()
                  ->comment('Usuario que registró el pago');

            $table->decimal('amount', 12, 2)
                  ->comment('Monto del pago al proveedor');

            $table->date('date')
                  ->comment('Fecha del pago');

            $table->enum('payment_method', ['efectivo', 'transferencia', 'cheque', 'otro'])
                  ->default('transferencia');

            $table->string('reference', 100)->nullable()
                  ->comment('Nº de transferencia, cheque, etc.');

            $table->text('notes')->nullable();

            // Vínculo automático al módulo de gastos
            // (se crea un Expense automáticamente al registrar el pago)
            $table->foreignId('expense_id')
                  ->nullable()
                  ->constrained('expenses')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
