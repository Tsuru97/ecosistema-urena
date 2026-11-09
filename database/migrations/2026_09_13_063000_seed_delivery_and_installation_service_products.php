<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. Servicio de Flete / Envío
        $fleteExists = DB::table('products')->where('sku', 'SERV-FLETE')->exists();
        if (!$fleteExists) {
            DB::table('products')->insert([
                'sku'         => 'SERV-FLETE',
                'name'        => 'Flete / Envío a Domicilio',
                'slug'        => 'servicio-flete-envio-domicilio',
                'description' => 'Servicio logístico de flete y transporte de mercancía para entrega a domicilio',
                'base_cost'   => 0.00,
                'type'        => 'service',
                'is_wildcard' => 0,
                'status'      => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        } else {
            DB::table('products')->where('sku', 'SERV-FLETE')->update([
                'name'        => 'Flete / Envío a Domicilio',
                'type'        => 'service',
                'is_wildcard' => 0,
                'status'      => 1,
                'updated_at'  => $now,
            ]);
        }

        // 2. Servicio de Armado e Instalación
        $armadoExists = DB::table('products')->where('sku', 'SERV-ARMADO')->exists();
        if (!$armadoExists) {
            DB::table('products')->insert([
                'sku'         => 'SERV-ARMADO',
                'name'        => 'Servicio de Armado e Instalación',
                'slug'        => 'servicio-armado-instalacion',
                'description' => 'Servicio especializado de armado, ensamble e instalación de muebles en sitio',
                'base_cost'   => 0.00,
                'type'        => 'service',
                'is_wildcard' => 0,
                'status'      => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        } else {
            DB::table('products')->where('sku', 'SERV-ARMADO')->update([
                'name'        => 'Servicio de Armado e Instalación',
                'type'        => 'service',
                'is_wildcard' => 0,
                'status'      => 1,
                'updated_at'  => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('products')->whereIn('sku', ['SERV-FLETE', 'SERV-ARMADO'])->delete();
    }
};
