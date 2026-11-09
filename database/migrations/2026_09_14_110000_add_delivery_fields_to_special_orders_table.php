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
        Schema::table('special_orders', function (Blueprint $table) {
            $table->boolean('is_delivery')->default(false)->after('total');
            $table->string('delivery_recipient_name')->nullable()->after('is_delivery');
            $table->string('delivery_phone', 30)->nullable()->after('delivery_recipient_name');
            $table->text('delivery_address')->nullable()->after('delivery_phone');
            $table->text('delivery_references')->nullable()->after('delivery_address');
            $table->decimal('delivery_lat', 10, 7)->nullable()->after('delivery_references');
            $table->decimal('delivery_lng', 10, 7)->nullable()->after('delivery_lat');
            $table->text('delivery_maps_url')->nullable()->after('delivery_lng');
            $table->date('delivery_date')->nullable()->after('delivery_maps_url');
            $table->string('delivery_time_slot', 50)->nullable()->after('delivery_date');
            $table->text('delivery_instructions')->nullable()->after('delivery_time_slot');
            $table->decimal('delivery_cost', 12, 2)->default(0)->after('delivery_instructions');
            $table->decimal('installation_cost', 12, 2)->default(0)->after('delivery_cost');
            $table->string('delivery_status', 30)->default('pendiente')->after('installation_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_delivery',
                'delivery_recipient_name',
                'delivery_phone',
                'delivery_address',
                'delivery_references',
                'delivery_lat',
                'delivery_lng',
                'delivery_maps_url',
                'delivery_date',
                'delivery_time_slot',
                'delivery_instructions',
                'delivery_cost',
                'installation_cost',
                'delivery_status',
            ]);
        });
    }
};
