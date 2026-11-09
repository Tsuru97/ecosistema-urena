<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('total');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
