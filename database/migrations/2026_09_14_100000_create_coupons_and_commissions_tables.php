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
        // 1. Tabla de Cupones
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                
                // Tipo de descuento: 'percentage' o 'fixed'
                $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('discount_value', 10, 2)->default(0.00);
                $table->decimal('max_discount_amount', 10, 2)->nullable(); // Tope máximo en descuentos porcentuales
                $table->decimal('min_purchase', 10, 2)->default(0.00); // Compra mínima requerida
                
                // Segmentación y pertenencia
                $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete(); // Vendedor comisionista / prospector
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete(); // Cupón personal de cliente
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete(); // Restringido a sucursal (null = todas)
                
                // Límites y vigencia
                $table->unsignedInteger('usage_limit')->nullable(); // Límite global de canjes (null = ilimitado)
                $table->unsignedInteger('usage_limit_per_customer')->default(1); // Límite de canjes por cliente
                $table->unsignedInteger('times_used')->default(0); // Contador de usos
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->boolean('status')->default(true); // Activo / Inactivo
                
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Tabla de Redenciones / Canjes de Cupones
        if (!Schema::hasTable('coupon_redemptions')) {
            Schema::create('coupon_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Cajero que registró la venta
                $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete(); // Vendedor en caja de la venta
                $table->decimal('discount_amount', 10, 2)->default(0.00); // Monto efectivamente descontado
                $table->timestamps();
            });
        }

        // 3. Agregar campos de cupón en la tabla sales si no existen
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'coupon_id')) {
                    $table->foreignId('coupon_id')->nullable()->after('customer_id')->constrained('coupons')->nullOnDelete();
                }
                if (!Schema::hasColumn('sales', 'coupon_code')) {
                    $table->string('coupon_code', 50)->nullable()->after('coupon_id');
                }
                if (!Schema::hasColumn('sales', 'coupon_discount')) {
                    $table->decimal('coupon_discount', 10, 2)->default(0.00)->after('coupon_code');
                }
            });
        }

        // 3.1 Agregar campos de cupón en la tabla quotes si no existen
        if (Schema::hasTable('quotes')) {
            Schema::table('quotes', function (Blueprint $table) {
                if (!Schema::hasColumn('quotes', 'coupon_id')) {
                    $table->foreignId('coupon_id')->nullable()->after('customer_id')->constrained('coupons')->nullOnDelete();
                }
                if (!Schema::hasColumn('quotes', 'coupon_code')) {
                    $table->string('coupon_code', 50)->nullable()->after('coupon_id');
                }
                if (!Schema::hasColumn('quotes', 'coupon_discount')) {
                    $table->decimal('coupon_discount', 10, 2)->default(0.00)->after('coupon_code');
                }
            });
        }

        // 4. Tabla de Comisiones de Venta (Matriz de Reparto)
        if (!Schema::hasTable('sale_commissions')) {
            Schema::create('sale_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete(); // Vendedor beneficiario
                $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
                
                // Rol en la transacción: 'direct_sale', 'coupon_owner' (prospección), 'closing_seller' (cajero/mostrador)
                $table->string('role', 30)->default('direct_sale');
                
                $table->decimal('sale_amount', 10, 2)->default(0.00); // Monto base de la venta
                $table->decimal('base_commission_rate', 5, 2)->default(0.00); // Tasa total de comisión de la venta (ej: 1.00%)
                $table->decimal('split_percentage', 5, 2)->default(100.00); // Porcentaje de participación según matriz (ej: 50.00% o 100.00%)
                $table->decimal('final_rate', 5, 2)->default(0.00); // Tasa efectiva asignada (base_rate * split_pct)
                $table->decimal('commission_amount', 10, 2)->default(0.00); // Monto ganado en pesos ($)
                
                // Estado: 'pending' (apartado sin liquidar), 'earned' (ganada/por pagar), 'paid' (liquidada en nómina), 'cancelled' (anulada)
                $table->string('status', 20)->default('earned');
                $table->dateTime('paid_at')->nullable(); // Fecha en que se liquidó al vendedor
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete(); // Administrador que liquidó
                $table->text('notes')->nullable();
                
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_commissions');
        
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (Schema::hasColumn('sales', 'coupon_id')) {
                    $table->dropForeign(['coupon_id']);
                    $table->dropColumn('coupon_id');
                }
                if (Schema::hasColumn('sales', 'coupon_code')) {
                    $table->dropColumn('coupon_code');
                }
                if (Schema::hasColumn('sales', 'coupon_discount')) {
                    $table->dropColumn('coupon_discount');
                }
            });
        }

        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};