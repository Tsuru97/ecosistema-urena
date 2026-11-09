<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attribute_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained('product_attribute')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_attribute_id', 'attribute_value_id'], 'pav_unique');
            $table->index('product_attribute_id', 'pav_pa_idx');
            $table->index('attribute_value_id', 'pav_av_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_value');
    }
};
