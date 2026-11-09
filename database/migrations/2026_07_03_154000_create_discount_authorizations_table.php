<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            
            // Quién lo pide y quién lo autoriza
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Código a validar (ej: 6 dígitos)
            $table->string('otp_code', 10);
            
            // Estado
            $table->string('status', 20)->default('pending')->comment('pending, approved, rejected');
            
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_authorizations');
    }
};
