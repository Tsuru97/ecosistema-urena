<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Limpiar caché de permisos de Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            [
                'name'        => 'auditar-cortes-caja',
                'guard_name'  => 'web',
                'name_public' => 'Auditar Todos los Cortes de Caja',
                'description' => 'Permite ver y auditar cortes de caja de todos los cajeros y todas las sucursales con indicadores de arqueo y descuadre.',
                'type'        => 'Caja',
            ],
            [
                'name'        => 'exportar-cortes-caja',
                'guard_name'  => 'web',
                'name_public' => 'Exportar Cortes de Caja (Excel)',
                'description' => 'Permite descargar el archivo CSV/Excel con el desglose de turnos y arqueos filtrados.',
                'type'        => 'Caja',
            ],
            [
                'name'        => 'reportes-cortes-caja',
                'guard_name'  => 'web',
                'name_public' => 'Reporte Ejecutivo de Caja (PDF)',
                'description' => 'Permite generar e imprimir el reporte ejecutivo consolidado de cortes de caja para auditoría y firmas.',
                'type'        => 'Caja',
            ],
        ];

        foreach ($permissions as $data) {
            $perm = Permission::firstOrCreate(
                ['name' => $data['name'], 'guard_name' => $data['guard_name']],
                [
                    'name_public' => $data['name_public'],
                    'description' => $data['description'],
                    'type'        => $data['type'],
                ]
            );
            $perm->update([
                'name_public' => $data['name_public'],
                'description' => $data['description'],
                'type'        => $data['type'],
            ]);
        }

        // Asignar por defecto a Dirección y Gerente
        $direccion = Role::where('name', 'Dirección')->first();
        if ($direccion) {
            $direccion->givePermissionTo(['auditar-cortes-caja', 'exportar-cortes-caja', 'reportes-cortes-caja']);
        }

        $gerente = Role::where('name', 'Gerente')->first();
        if ($gerente) {
            $gerente->givePermissionTo(['auditar-cortes-caja', 'exportar-cortes-caja', 'reportes-cortes-caja']);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'auditar-cortes-caja',
            'exportar-cortes-caja',
            'reportes-cortes-caja',
        ])->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
