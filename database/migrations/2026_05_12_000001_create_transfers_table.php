<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('origin_store_id')
                  ->constrained('stores')
                  ->restrictOnDelete();

            $table->foreignId('destination_store_id')
                  ->constrained('stores')
                  ->restrictOnDelete();

            $table->string('folio')->unique(); // TRP-2026-0001

            $table->enum('status', [
                'pendiente',   // Creado, esperando autorización de envío
                'en_transito', // Enviado por la sucursal origen
                'recibido',    // Confirmado por la sucursal destino
                'rechazado',   // Rechazado por la sucursal destino
                'cancelado',   // Cancelado antes de enviar
                'devolucion',  // Devolución
            ])->default('pendiente');

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();   // Quien lo crea
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();      // Quien lo envía
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();  // Quien recibe/rechaza

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->text('notes')->nullable();           // Notas del creador
            $table->text('rejection_reason')->nullable();// Razón de rechazo

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('origin_store_id');
            $table->index('destination_store_id');
            $table->index('folio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
