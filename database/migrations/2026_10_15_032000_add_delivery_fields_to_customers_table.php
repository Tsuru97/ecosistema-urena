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
            $table->string('street')->nullable()->after('legal_address')->comment('Calle y número exterior e interior');
            $table->string('colonia')->nullable()->after('street')->comment('Colonia o fraccionamiento');
            $table->string('city', 100)->nullable()->after('colonia')->comment('Ciudad o municipio');
            $table->string('state', 100)->nullable()->after('city')->comment('Estado');
            $table->string('zip', 10)->nullable()->after('state')->comment('Código postal');
            $table->text('delivery_references')->nullable()->after('zip')->comment('Referencias del domicilio de entrega');
            $table->text('delivery_instructions')->nullable()->after('delivery_references')->comment('Instrucciones especiales para el chofer');
            $table->decimal('delivery_lat', 10, 7)->nullable()->after('delivery_instructions')->comment('Coordenada GPS Latitud');
            $table->decimal('delivery_lng', 10, 7)->nullable()->after('delivery_lat')->comment('Coordenada GPS Longitud');
            $table->text('delivery_maps_url')->nullable()->after('delivery_lng')->comment('Enlace directo a Google Maps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'street',
                'colonia',
                'city',
                'state',
                'zip',
                'delivery_references',
                'delivery_instructions',
                'delivery_lat',
                'delivery_lng',
                'delivery_maps_url',
            ]);
        });
    }
};
