<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('store_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week');       // 0=Lunes … 6=Domingo
            $table->boolean('is_open')->default(true);
            $table->time('open_time')->nullable();     // Hora apertura
            $table->time('close_time')->nullable();    // Hora cierre
            $table->time('break_start')->nullable();   // Inicio receso
            $table->time('break_end')->nullable();     // Fin receso
            $table->timestamps();

            $table->unique(['store_id', 'day_of_week']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('store_schedules');
    }
};
