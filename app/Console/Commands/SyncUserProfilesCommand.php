<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UserProfileSyncService;

class SyncUserProfilesCommand extends Command
{
    /**
     * El nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'db:sync-user-profiles';

    /**
     * La descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Sincroniza y repara los expedientes de Clientes, Proveedores y asignaciones de sucursal para usuarios huérfanos en la base de datos.';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle(UserProfileSyncService $syncService): int
    {
        $this->info('Iniciando escaneo y reparación de usuarios huérfanos...');

        $stats = $syncService->syncAllOrphanProfiles();

        $this->newLine();
        $this->info('✅ Sincronización completada exitosamente.');
        $this->newLine();

        $this->table(
            ['Métrica de Reparación', 'Cantidad Procesada'],
            [
                ['Expedientes de Cliente creados', number_format($stats['customers_created'])],
                ['Expedientes de Proveedor creados', number_format($stats['suppliers_created'])],
                ['Roles asignados a expedientes', number_format($stats['roles_assigned'])],
                ['Sucursales asignadas a usuarios sin tienda', number_format($stats['stores_assigned'])],
            ]
        );

        return Command::SUCCESS;
    }
}
