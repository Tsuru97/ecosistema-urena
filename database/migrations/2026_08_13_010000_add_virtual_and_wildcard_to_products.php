<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extender el ENUM type (simple, variable) añadiendo virtual y service
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE products
                MODIFY COLUMN type ENUM('simple','variable','virtual','service')
                NOT NULL DEFAULT 'simple'
            ");
        }

        // Flag para el producto comodín configurable desde el POS
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_wildcard')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_wildcard');
        });

        // Revertir ENUM al estado original (solo si no hay registros virtual/service)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE products
                MODIFY COLUMN type ENUM('simple','variable')
                NOT NULL DEFAULT 'simple'
            ");
        }
    }
};
