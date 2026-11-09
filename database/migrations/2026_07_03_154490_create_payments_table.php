<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->comment('Cajero que recibió el abono');
            
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 100)->nullable()->comment('Folio de terminal o transferencia');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
