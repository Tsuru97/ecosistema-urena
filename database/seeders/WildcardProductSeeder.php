<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Setting;

class WildcardProductSeeder extends Seeder
{
    public function run(): void
    {
        // Crear el producto comodín "Personalizado" si no existe
        Product::firstOrCreate(
            ['is_wildcard' => true],
            [
                'sku'         => 'CUSTOM-001',
                'name'        => 'Producto Personalizado',
                'slug'        => 'producto-personalizado',
                'description' => 'Producto configurable desde el POS. El cajero define el nombre, descripción y precio al momento de la venta.',
                'type'        => 'virtual',
                'is_wildcard' => true,
                'status'      => true,
                'base_cost'   => 0,
                'ieps_type'   => 'exempt',
                'ieps_value'  => 0,
            ]
        );

        // Setting: porcentaje mínimo de anticipo para Pedidos Especiales (global)
        Setting::firstOrCreate(
            ['key' => 'special_order_min_deposit', 'store_id' => null],
            [
                'value' => '20',
                'type'  => 'integer',
                'group' => 'ventas',
            ]
        );
    }
}
