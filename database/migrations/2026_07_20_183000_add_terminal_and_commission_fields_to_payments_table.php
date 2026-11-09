<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('payment_terminal_id')->nullable()->after('payment_method_id')->constrained('payment_terminals')->nullOnDelete();
            $table->decimal('terminal_commission', 12, 2)->default(0.00)->after('payment_terminal_id');
            $table->string('commission_charged_to', 20)->nullable()->after('terminal_commission');
            $table->integer('msi_applied')->default(0)->after('commission_charged_to');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_terminal_id');
            $table->dropColumn(['terminal_commission', 'commission_charged_to', 'msi_applied']);
        });
    }
};
