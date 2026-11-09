<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\User;

class StoreSeeder extends Seeder
{
    public function run()
    {
        $principal = Store::firstOrCreate(
            ['code' => 'MATZ'],
            [
                'name'   => 'Sucursal Matriz',
                'city'   => 'Villa de Álvarez',
                'status' => true,
            ]
        );

        Store::firstOrCreate(
            ['code' => 'GALV'],
            [
                'name'   => 'Sucursal Galván',
                'city'   => 'Colima',
                'status' => true,
            ]
        );

        // Asignar primera tienda como default al admin existente
        $admin = User::first();
        if ($admin) {
            $admin->stores()->syncWithoutDetaching([
                $principal->id => ['is_default' => true]
            ]);
        }
    }
}
