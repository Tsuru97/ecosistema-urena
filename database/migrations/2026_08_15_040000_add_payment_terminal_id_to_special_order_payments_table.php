<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('special_order_payments') && !Schema::hasColumn('special_order_payments', 'payment_terminal_id')) {
            Schema::table('special_order_payments', function (Blueprint $table) {
                $table->foreignId('payment_terminal_id')
                      ->nullable()
                      ->after('payment_method_id')
                      ->constrained('payment_terminals')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('special_order_payments') && Schema::hasColumn('special_order_payments', 'payment_terminal_id')) {
            Schema::table('special_order_payments', function (Blueprint $table) {
                $table->dropForeign(['payment_terminal_id']);
                $table->dropColumn('payment_terminal_id');
            });
        }
    }
};
