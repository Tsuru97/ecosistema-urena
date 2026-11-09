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
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'delivery_time')) {
                $table->time('delivery_time')->nullable()->after('delivery_date');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'delivery_time')) {
                $table->time('delivery_time')->nullable()->after('delivery_date');
            }
            if (!Schema::hasColumn('sales', 'delivery_evidence_path')) {
                $table->string('delivery_evidence_path')->nullable()->after('delivery_status');
            }
            if (!Schema::hasColumn('sales', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('delivery_evidence_path');
            }
            if (!Schema::hasColumn('sales', 'delivered_by')) {
                $table->unsignedBigInteger('delivered_by')->nullable()->after('delivered_at');
            }
        });

        Schema::table('special_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('special_orders', 'delivery_time')) {
                $table->time('delivery_time')->nullable()->after('delivery_date');
            }
            if (!Schema::hasColumn('special_orders', 'delivery_evidence_path')) {
                $table->string('delivery_evidence_path')->nullable()->after('delivery_status');
            }
            if (!Schema::hasColumn('special_orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('delivery_evidence_path');
            }
            if (!Schema::hasColumn('special_orders', 'delivered_by')) {
                $table->unsignedBigInteger('delivered_by')->nullable()->after('delivered_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (Schema::hasColumn('quotes', 'delivery_time')) {
                $table->dropColumn('delivery_time');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            $cols = array_filter(
                ['delivery_time', 'delivery_evidence_path', 'delivered_at', 'delivered_by'],
                fn($c) => Schema::hasColumn('sales', $c)
            );
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('special_orders', function (Blueprint $table) {
            $cols = array_filter(
                ['delivery_time', 'delivery_evidence_path', 'delivered_at', 'delivered_by'],
                fn($c) => Schema::hasColumn('special_orders', $c)
            );
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
