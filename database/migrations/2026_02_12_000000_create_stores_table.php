<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // "Sucursal Centro"
            $table->string('code', 10)->unique();      // "CEN", "NOR", "SUR"
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip', 10)->nullable();
            $table->decimal('lat', 10, 7)->nullable(); // Geolocalización
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('logo')->nullable();        // Ruta a logo de sucursal
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stores');
    }
};
