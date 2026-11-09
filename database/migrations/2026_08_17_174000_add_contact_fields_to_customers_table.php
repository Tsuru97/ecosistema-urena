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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id')->comment('Nombre del cliente o contacto');
            $table->string('lastname')->nullable()->after('name')->comment('Apellido del cliente o contacto');
            $table->string('email')->nullable()->after('lastname')->comment('Correo de contacto del cliente');
            $table->string('phone', 20)->nullable()->after('email')->comment('Teléfono de contacto del cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['name', 'lastname', 'email', 'phone']);
        });
    }
};
