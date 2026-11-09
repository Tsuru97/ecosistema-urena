<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('transfers', 'receiver_notes')) {
                $table->text('receiver_notes')->nullable()->after('rejection_reason');
            }
        });

        // Modificar la columna 'status' para incluir el nuevo tipo 'devolucion' de forma segura
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE transfers MODIFY COLUMN status ENUM('pendiente', 'en_transito', 'recibido', 'rechazado', 'cancelado', 'devolucion') NOT NULL DEFAULT 'pendiente'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            if (Schema::hasColumn('transfers', 'receiver_notes')) {
                $table->dropColumn('receiver_notes');
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE transfers MODIFY COLUMN status ENUM('pendiente', 'en_transito', 'recibido', 'rechazado', 'cancelado') NOT NULL DEFAULT 'pendiente'");
        }
    }
};
