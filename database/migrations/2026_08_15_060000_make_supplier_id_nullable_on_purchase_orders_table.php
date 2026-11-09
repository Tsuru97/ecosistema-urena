<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        try {
            DB::statement("ALTER TABLE purchase_orders MODIFY supplier_id BIGINT UNSIGNED NULL");
        } catch (\Throwable $e) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('supplier_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE purchase_orders MODIFY supplier_id BIGINT UNSIGNED NOT NULL");
        } catch (\Throwable $e) {
            // Silence
        }
    }
};
