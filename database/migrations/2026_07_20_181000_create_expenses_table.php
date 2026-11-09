<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete()->comment('Sucursal donde se genera el gasto');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Usuario/Cajero que registró el gasto');
            
            $table->string('concept');
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->date('date');
            
            $table->string('reference_type')->nullable()->comment('Modelo referenciado, ej: payment');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID del registro de referencia');
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
