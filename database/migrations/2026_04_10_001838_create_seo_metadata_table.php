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
        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            
            // Relación Polimórfica (seoable_id, seoable_type)
            $table->morphs('seoable');
            
            // Meta etiquetas básicas
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable(); // Guardado opcionalmente como string separada por comas
            
            // URL Canónica
            $table->string('canonical_url')->nullable();
            
            // Rastreo para motores de búsqueda (ej. "index, follow", "noindex, nofollow")
            $table->string('robots')->default('index, follow');
            
            // OpenGraph (Redes Sociales)
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            
            // ID opcional a imagen en la tabla files para OpenGraph/Twiter Cards
            $table->unsignedBigInteger('og_image_id')->nullable(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_metadata');
    }
};
