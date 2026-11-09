<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Efectivo', 'commission_percentage' => 0.00, 'is_system' => true],
            ['name' => 'Transferencia / SPEI', 'commission_percentage' => 0.00, 'is_system' => true],
            ['name' => 'Tarjeta de Débito', 'commission_percentage' => 2.50, 'is_system' => true],
            ['name' => 'Tarjeta de Crédito', 'commission_percentage' => 3.50, 'is_system' => true],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}
