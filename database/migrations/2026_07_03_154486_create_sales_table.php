<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->comment('Vendedor/Cajero');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            
            $table->string('ticket_folio', 50)->unique();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('msi_applied')->default(0)->comment('0 = sin msi, 3, 6, 9, 12, etc.');
            
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            
            // Layaway (Apartado) support
            $table->decimal('amount_paid', 12, 2)->default(0)->comment('Suma de pagos recibidos');
            $table->decimal('balance', 12, 2)->default(0)->comment('Deuda restante');
            
            $table->string('status', 30)->default('pagada')->comment('pagada, apartado, entregada, cancelada');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
