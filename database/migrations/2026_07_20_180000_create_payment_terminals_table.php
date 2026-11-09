<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terminals', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('commission_fixed', 12, 2)->default(0.00)->comment('Comisión fija cobrada por la terminal');
            $table->decimal('commission_percentage', 5, 2)->default(0.00)->comment('Comisión en porcentaje cobrada por la terminal');
            $table->boolean('charge_client')->default(true)->comment('True si la comisión se cobra al cliente, false si es gasto para la empresa');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_terminal_payment_method', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_terminal_id')
                ->constrained('payment_terminals')
                ->cascadeOnDelete()
                ->name('ptpm_terminal_fk');
            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->cascadeOnDelete()
                ->name('ptpm_method_fk');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terminal_payment_method');
        Schema::dropIfExists('payment_terminals');
    }
};
